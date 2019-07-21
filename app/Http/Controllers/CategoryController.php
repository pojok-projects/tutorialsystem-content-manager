<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use function GuzzleHttp\json_decode;

class CategoryController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    private function validator($content, $id)
    {
        $data = json_decode($content->getBody(), true);
        $code = $content->getStatusCode();
        if ($code == 404) {
            return response()->json([
                'code' => 404,
                'message' => 'Not Found'
            ], 404);
        } else {
            if ($code != 200) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Bad Geteway'
                ], 500);
            }
        }
        $data = ($data['result']);
        $i = 0;
        $result = null;
        foreach ($data as $d) {
            if (isset($d['category_id'])) {
                if ($d['category_id'] == $id) {
                    $result[$i] = $d;
                    $i++;
                }
            }
        }
        //haha
        if (is_null($result)) {
            return response()->json([
                'message' => 'Not Found',
                'code' => 404
            ], 404);
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'message' => 'earch query has been performed, data has been found',
                'total' => count($result)
            ],
            'result' => $result
        ], 200);
    }

    public function list($id = null)
    {
        if (is_null($id)) {
            $content = $this->client->request('GET', env('ENDPOINT_API') . 'content/category');
            if ($content->getStatusCode() != 200) {
                return response()->json([
                    'status' => [
                        'code' => '500',
                        'message' => 'Bad Gateway',
                    ]
                ], 500);
            } else {
                return response()->json(json_decode($content->getBody(), true), 200);
            }
        } else {
            $content = $this->client->request('GET', env('ENDPOINT_API') . 'content/metadata');

            $result = $this->validator($content, $id);

            return $result;
        }
    }

    public function search_videos(Request $request)
    {

        if (isset($request->title) && !isset($request->genre)) {
            $rules = [
                'title' => 'required',
                'category_id' => 'required'
            ];
            $message = [
                'required' => 'Please Fill Attribute :attribut'
            ];
            $this->validate($request, $rules, $message);

            $data = $this->client->request('POST', env('ENDPOINT_API') . 'content/metadata/search', [
                'query' => urlencode('"video_title=' . $request->title . '"')
            ]);

            $result = $this->validator($data, $request->category_id);

            return $result;
        } elseif (isset($request->genre) && !isset($request->title)) {
            $rules = [
                'genre' => 'required',
                'category_id' => 'required'
            ];
            $message = [
                'required' => 'Please Fill Attribute :attribut'
            ];
            $this->validate($request, $rules, $message);

            $content = $this->client->request('POST', env('ENDPOINT_API') . 'content/metadata/search', [
                'query' => urlencode('"video_genre=' . $request->genre . '"')
            ]);


            $result = $this->validator($content, $request->category_id);

            return $result;
        } elseif (isset($request->title) && isset($request->genre)) {
            $rules = [
                'genre' => 'required',
                'title' => 'required',
                'category_id' => 'required'
            ];
            $message = [
                'required' => 'Please Fill Attribute :attribut'
            ];
            $this->validate($request, $rules, $message);

            $content = $this->client->request('POST', env('ENDPOINT_API') . 'content/metadata/search', [
                'query' => urlencode('"video_title=' . $request->title . ',video_genre=' . $request->genre . '"')
            ]);


            $result = $this->validator($content, $request->category_id);

            return $result;
        } else {
            return response()->json([
                'message' => 'Bad Request',
                'code' => 400
            ], 400);
        }
    }
}
