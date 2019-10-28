<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class commentController extends Controller
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

    private function get_comments($id) {
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
        
        $data = $result['comments'];
        return $data;
    }

    public function get($id)
    {
        $result = $this->get_comments($id);

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
                'message' => 'comments gotten'
            ],
            'result' => [
                'total' => count($result),
                'comments' => $result
            ]
        ]);
    }

    public function add(Request $request, $id)
    {
        $rules = [
            'user_id' => 'required',
            'message' => 'required'
        ];
        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);


        $uuid = (string) str::uuid();
        $comment = array([
            'id' => $uuid,
            'user_id' => $request->user_id,
            'reply_id' => isset($request->reply_id) ? $request->reply_id : [],
            'message' => $request->message,
            'updated_at' => date(DATE_ATOM),
            'created_at' => date(DATE_ATOM)
        ]);
        
        $comments = $this->get_comments($id);

        if($comments == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($comments == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }

        $comments = is_null($comments) ? [] : $comments;

        $result = array_merge($comments, $comment);
        
        $data = $this->client->request('POST', $this->endpoint . "content/metadata/update/$id", [
            'form_params' => [
                'comments' => $result
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
                'comment_id' => $uuid
            ]
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'metadata_id' => 'required',
            'comment_id' => 'required'
        ];

        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);

        $comments = $this->get_comments($request->metadata_id);

        if($comments == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($comments == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }

        $comments = is_null($comments) ? [] : $comments;

        $key = array_search($request->comment_id, array_column($comments, 'id'));
        if($key === false) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'comment not found'
                ]
            ]);
        } else {
            $update = $comments[$key];
            unset($comments[$key]);
        }
        
        $user_id = (isset($request->user_id)) ? $request->user_id : $update['user_id'];
        $reply_id = (isset($request->reply_id)) ? $request->reply_id : $update['reply_id'];
        $message = (isset($request->message)) ? $request->message : $update['message'];

        $comment = array([
            'id' => $update['id'],
            'user_id' => $user_id,
            'message' => $message,
            'reply_id' => $reply_id,
            'updated_at' => date(DATE_ATOM),
            'created_at' => $update['created_at']
        ]);

        $result = $this->client->request('POST', $this->endpoint . "content/metadata/update/$request->metadata_id", [
            'form_params' => [
                'comments' => array_merge($comments, $comment)
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
            'comment_id' => 'required'
        ];

        $message = [
            'required' => 'Please fill attribute :attribute'
        ];

        $this->validate($request, $rules, $message);

        $comment = $this->get_comments($request->metadata_id);

        $key = array_search($request->comment_id, array_column($comment, 'id'));
        if($key === false) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'comment not found'
                ]
            ]);
        } else {
            unset($comment[$key]);
        }

        $comment = array_merge([], $comment);

        if($comment == 404) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'metadata not found'
                ]
                ], 404);
        } elseif($comment == 502) {
            return response()->json([
                'status' => [
                    'code' => 502,
                    'message' => 'Bad Gateway'
                ]
                ], 502);
        }

        $comment = is_null($comment) ? [] : $comment;

        $result = $this->client->request('POST', $this->endpoint . "content/metadata/update/$request->metadata_id", [
            'form_params' => [
                'comments' => $comment
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