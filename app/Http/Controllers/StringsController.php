<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Strings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class StringsController extends Controller
{

    public function analyseString(Request $request)
    {
        //Validate request
        $validated = $this->validateRequest($request);

        //Check for existing string
        if ($this->stringExists($validated['value'])) {
            return response()->json([
                'message' => 'String already exists'
            ], 409);
        }

        //Analyze string
        $analysis = $this->analyzeStringProperties($validated['value']);

        //Save to database
        $record = $this->storeStringRecord($validated['value'], $analysis);

        //Return response
        return $this->respondWithAnalysis($validated['value'], $analysis, $record);
    }


    private function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string',
        ]);

        if ($validator->fails()) {
            response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422)->send();
            exit;
        }

        return $validator->validated();
    }


    private function stringExists(string $value): bool
    {
        return Strings::where('value', $value)->exists();
    }


    private function analyzeStringProperties(string $value): array
    {
        $length = strlen($value);
        $is_palindrome = strtolower($value) === strrev(strtolower($value));
        $unique_characters = count(array_unique(mb_str_split($value)));
        $word_count = str_word_count($value);
        $sha256_hash = hash('sha256', $value);
        
        $char_freq = [];
        foreach (mb_str_split($value) as $char) {
            $char_freq[$char] = ($char_freq[$char] ?? 0) + 1;
        }

        return compact(
            'length',
            'is_palindrome',
            'unique_characters',
            'word_count',
            'sha256_hash',
            'char_freq'
        );
    }



    private function storeStringRecord(string $value, array $analysis)
    {
        return Strings::create([
            'value' => $value,
            'length' => $analysis['length'],
            'is_palindrome' => $analysis['is_palindrome'],
            'unique_characters' => $analysis['unique_characters'],
            'word_count' => $analysis['word_count'],
            'sha256_hash' => $analysis['sha256_hash'],
            'character_frequency_map' => json_encode($analysis['char_freq']),
        ]);
    }



    private function respondWithAnalysis(string $value, array $analysis, $record)
    {
        return response()->json([
            'id' => $analysis['sha256_hash'],
            'value' => $value,
            'properties' => [
                'length' => $analysis['length'],
                'is_palindrome' => $analysis['is_palindrome'],
                'unique_characters' => $analysis['unique_characters'],
                'word_count' => $analysis['word_count'],
                'sha256_hash' => $analysis['sha256_hash'],
                'character_frequency_map' => $analysis['char_freq'],
            ],
            'created_at' => $record->created_at->toISOString(),
        ], 201);
    }





    //Check if string exist is db
    public function checkString($string_value)
    {

        $record = Strings::where('value', $string_value)->first();

        if (!$record) {
            return response()->json([
                'error' => 'String does not exist in the system'
            ], 404);
        }

        $char_freq = json_decode($record->character_frequency_map, true);

        return response()->json([
            'id' => $record->sha256_hash,
            'value' => $record->value,
            'properties' => [
                'length' => $record->length,
                'is_palindrome' => (bool) $record->is_palindrome,
                'unique_characters' => $record->unique_characters,
                'word_count' => $record->word_count,
                'sha256_hash' => $record->sha256_hash,
                'character_frequency_map' => $char_freq,
            ],
            'created_at' => $record->created_at->toISOString(),
        ], 200);
    }



    //filter string 
    public function filterStrings(Request $request)
    {
        $query = Strings::query();

        if ($request->has('is_palindrome')) {
            $is_palindrome = filter_var($request->query('is_palindrome'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_palindrome', $is_palindrome);
        }

        if ($request->has('min_length')) {
            $query->where('length', '>=', (int) $request->query('min_length'));
        }

        if ($request->has('max_length')) {
            $query->where('length', '<=', (int) $request->query('max_length'));
        }

        if ($request->has('word_count')) {
            $query->where('word_count', (int) $request->query('word_count'));
        }

        if ($request->has('contains_character')) {
            $character = $request->query('contains_character');
            $query->where('value', 'LIKE', '%' . $character . '%');
        }

        $results = $query->get();

        // If no matches found
        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'No matching strings found.'
            ], 404);
        }

        $response = $results->map(function ($record) {
            return [
                'id' => $record->sha256_hash,
                'value' => $record->value,
                'properties' => [
                    'length' => $record->length,
                    'is_palindrome' => (bool) $record->is_palindrome,
                    'unique_characters' => $record->unique_characters,
                    'word_count' => $record->word_count,
                    'sha256_hash' => $record->sha256_hash,
                    'character_frequency_map' => json_decode($record->character_frequency_map, true),
                ],
                'created_at' => $record->created_at->toISOString(),
            ];
        });

        return response()->json($response, 200);
    }




    //natural language filter
    public function filterByNaturalLanguage(Request $request)
    {
        $originalQuery = $request->query('query');

        if (!$originalQuery) {
            return response()->json([
                'error' => 'Query parameter is required.'
            ], 400);
        }

        $result = $this->interpretNaturalLanguageQuery($originalQuery);

        if ($result === 'unparseable') {
            return response()->json([
                'error' => 'Unable to parse natural language query.'
            ], 400);
        }

        if ($result === 'conflict') {
            return response()->json([
                'error' => 'Query parsed but resulted in conflicting filters.'
            ], 422);
        }

        $filters = $result;

        $query = Strings::query();

        if (isset($filters['is_palindrome'])) {
            $query->where('is_palindrome', $filters['is_palindrome']);
        }

        if (isset($filters['word_count'])) {
            $query->where('word_count', $filters['word_count']);
        }

        if (isset($filters['min_length'])) {
            $query->where('length', '>=', $filters['min_length']);
        }

        if (isset($filters['max_length'])) {
            $query->where('length', '<=', $filters['max_length']);
        }

        if (isset($filters['contains_character'])) {
            $query->where('value', 'LIKE', '%' . $filters['contains_character'] . '%');
        }

        $results = $query->get();

        return response()->json([
            'data' => $results->map(function ($record) {
                return [
                    'id' => $record->sha256_hash,
                    'value' => $record->value,
                    'properties' => [
                        'length' => $record->length,
                        'is_palindrome' => (bool) $record->is_palindrome,
                        'unique_characters' => $record->unique_characters,
                        'word_count' => $record->word_count,
                        'sha256_hash' => $record->sha256_hash,
                        'character_frequency_map' => json_decode($record->character_frequency_map, true),
                    ],
                    'created_at' => $record->created_at->toISOString(),
                ];
            }),
            'count' => $results->count(),
            'interpreted_query' => [
                'original' => $originalQuery,
                'parsed_filters' => $filters,
            ]
        ], 200);
    }



    //filter by natiural language

    private function interpretNaturalLanguageQuery(string $query): array|string
    {
        $query = strtolower($query);
        $filters = [];
    
        // Palindrome
        if (str_contains($query, 'palindromic') || str_contains($query, 'palindrome')) {
            $filters['is_palindrome'] = true;
        }
    
        // Word count
        if (preg_match('/(?:single|one) word/', $query)) {
            $filters['word_count'] = 1;
        } elseif (preg_match('/(\d+)\s+word[s]?/', $query, $matches)) {
            $filters['word_count'] = (int) $matches[1];
        }
    
        // Length filters
        if (preg_match('/longer than (\d+)/', $query, $matches)) {
            $filters['min_length'] = (int) $matches[1] + 1;
        }
    
        if (preg_match('/shorter than (\d+)/', $query, $matches)) {
            $filters['max_length'] = (int) $matches[1] - 1;
        }
    
        // Contains letter(s)
        if (preg_match('/containing (?:the letter|letters?) ([a-z]+)/', $query, $matches)) {
            $filters['contains_character'] = $matches[1];
        }
    
        // First vowel heuristic
        if (str_contains($query, 'first vowel')) {
            $filters['contains_character'] = 'aeiou'; // broadened
        }
    
        if (empty($filters)) {
            return 'unparseable';
        }
    
        // Conflict detection
        if (
            isset($filters['min_length'], $filters['max_length']) &&
            $filters['min_length'] > $filters['max_length']
        ) {
            return 'conflict';
        }
    
        return $filters;
    }


    //Delete string
    public function deleteString($string_value)
    {
        $stringRecord = Strings::where('value', $string_value)->first();

        if (!$stringRecord) {
            return response()->json([
                'error' => 'String does not exist in the system'
            ], 404);
        }

        $stringRecord->delete();

        // 204 No Content - empty response body
        return response()->noContent();
    }



}
