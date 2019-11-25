<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class reactController extends Controller
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

    private function get_react($react, $id) {
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
        
        $data = is_null($result[$react . 's']) ? [] : $result[$react . 's'];
        return $data;
    }

    public function get($react,$id)
    {
        if($react != 'like') {
            if($react != 'dislike') {
                return response()->json([
                    'status' => [
                        'code' => 503,
                        'message' => 'bad gateway'
                    ]
                ]);
            } else {
                $react = 'dislike';
            }
        } else {
            $react = 'like';
        }
        $result = $this->get_react($react, $id);
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
                'message' => $react . ' gotten'
            ],
            'result' => [
                'total' => count($result),
                $react . 's' => $result
            ]
        ]);
    }

    public function add(Request $request, $react, $id)
    {
        if($react != 'like') {
            if($react != 'dislike') {
                return response()->json([
                    'status' => [
                        'code' => 503,
                        'message' => 'bad gateway'
                    ]
                ]);
            } else {
                $react = 'dislike';
            }
        } else {
            $react = 'like';
        }

        $rules = [
            'user_id' => 'required'
        ];
        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);


        $uuid = (string) str::uuid();
        $content = array([
            'id' => $uuid,
            'user_id' => $request->user_id,
            'file_path' => $request->file_path,
            'updated_at' => date(DATE_ATOM),
            'created_at' => date(DATE_ATOM)
        ]);
        
        $reacts = $this->get_react($react, $id);

        if($reacts == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($reacts == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }

        $reacts = is_null($reacts) ? [] : $reacts;
        $result = array_merge($reacts, $content);
        
        $data = $this->client->request('POST', $this->endpoint . "content/metadata/update/$id", [
            'json' => [
                $react . 's' => $result
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
                $react . '_id' => $uuid
            ]
        ]);
    }


    public function destroy(Request $request, $react)
    {
        if($react != 'like') {
            if($react != 'dislike') {
                return response()->json([
                    'status' => [
                        'code' => 503,
                        'message' => 'bag gateway'
                    ]
                ]);
            } else {
                $react = 'dislike';
            }
        } else {
            $react = 'like';
        }

        $rules = [
            'metadata_id' => 'required',
            'user_id' => 'required'
        ];

        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);

        $reacts = $this->get_react($react, $request->metadata_id);

        if($react == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($react == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }
        
        $reacts = is_null($react) ? [] : $reacts;

        $key = array_search($request->user_id, array_column($reacts, 'user_id'));
        if($key === false) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => $react .' not found'
                ]
            ]);
        } else {
            unset($reacts[$key]);
        }

        $reacts = array_merge([], $reacts);

        $result = $this->client->request('POST', $this->endpoint . "content/metadata/update/$request->metadata_id", [
            'json' => [
                $react . 's' => $reacts
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
