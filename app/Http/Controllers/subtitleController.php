<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class subtitleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $client, $endpoint;

    public function __construct()
    {
        $this->client = new Client();
        $this->endpoint = env('ENDPOINT_API');
    }

    private function get_subtitles($id) {
        $result = $this->client->request('GET', $this->endpoint . "content/metadata/$id");
        if($result->getStatusCode() == 502) {
            return 502;
        }
        
        
        $result = json_decode($result->getBody(), true);
        if(isset($result['status'])) {
            if($result['status']['code'] == 404) {
                return 404;
            }
        }
        
        $data = $result['subtitle'];
        return $data;
    }

    public function get($id)
    {
        $result = $this->get_subtitles($id);
        if($result == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($result == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }
        return response()->json([
            'status' =>[
                'code' => 200,
                'message' => 'subtitle gotten'
            ],
            'result' => [
                'total' => count($result),
                'subtitles' => $result
            ]
        ]);
    }

    public function add(Request $request, $id)
    {
        $rules = [
            'subtitle_category_id' => 'required',
            'file_path' => 'required'
        ];
        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);


        $uuid = (string) str::uuid();
        $subtitle = array([
            'id' => $uuid,
            'subtitle_category_id' => $request->subtitle_category_id,
            'file_path' => $request->file_path,
            'updated_at' => date(DATE_ATOM),
            'created_at' => date(DATE_ATOM)
        ]);
        
        $subtitles = $this->get_subtitles($id);

        if($subtitles == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($subtitles == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }

        $subtitles = is_null($subtitles) ? [] : $subtitles;
        $result = array_merge($subtitles, $subtitle);
        
        $data = $this->client->request('POST', $this->endpoint . "content/metadata/update/$id", [
            'form_params' => [
                'subtitle' => $result
            ]
        ]);

        if($data->getStatusCode() != 200) {
            return response()->json([
                'Message' => 'Bad Gateway'
            ], $data->getStatusCode());
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'message' => 'upload success'
            ],
            'result' => [
                'subtitle_id' => $uuid
            ]
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'metadata_id' => 'required',
            'subtitle_id' => 'required'
        ];

        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);

        $subtitles = $this->get_subtitles($request->metadata_id);
        if($subtitles == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($subtitles == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }
        $subtitles = is_null($subtitles) ? [] : $subtitles;

        $key = array_search($request->subtitle_id, array_column($subtitles, 'id'));
        if($key === false) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'subtitle not found'
                ]
            ]);
        } else {
            $update = $subtitles[$key];
            unset($subtitles[$key]);
        }
        
        $subtitle_category_id = (isset($request->subtitle_category_id)) ? $request->subtitle_category_id : $update['subtitle_category_id'];
        $file_path = (isset($request->file_path)) ? $request->file_path : $update['file_path'];

        $subtitle = array([
            'id' => $update['id'],
            'subtitle_category_id' => $subtitle_category_id,
            'file_path' => $file_path,
            'updated_at' => date(DATE_ATOM),
            'created_at' => $update['created_at']
        ]);

        $result = $this->client->request('POST', $this->endpoint . "content/metadata/update/$request->metadata_id", [
            'form_params' => [
                'subtitle' => array_merge($subtitles, $subtitle)
            ]
        ]);

        if($result->getStatusCode() != 200) {
            return response()->json([
                'Message' => 'Bad Gateway'
            ], $result->getStatusCode());
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'message' => 'Update Success'
            ]
        ], 200);

    }

    public function destroy(Request $request)
    {
        $rules = [
            'metadata_id' => 'required',
            'subtitle_id' => 'required'
        ];

        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);

        $subtitle = $this->get_subtitles($request->metadata_id);

        if($subtitle == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($subtitle == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }
        
        $subtitle = is_null($subtitle) ? [] : $subtitle;

        $key = array_search($request->subtitle_id, array_column($subtitle, 'id'));
        if($key === false) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'subtitle not found'
                ]
            ]);
        } else {
            unset($subtitle[$key]);
        }

        $subtitle = array_merge([], $subtitle);

        $result = $this->client->request('POST', $this->endpoint . "content/metadata/update/$request->metadata_id", [
            'form_params' => [
                'subtitle' => $subtitle
            ]
        ]);

        if($result->getStatusCode() != 200) {
            return response()->json([
                'status' => [
                    'code' => $result->getStatusCode(),
                    'message' => 'Bad Gateway'
                ]
                ], $result->getStatusCode());
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'message' => 'Delete Success'
            ]
        ]);
    }


    //
}