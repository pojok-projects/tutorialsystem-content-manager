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

    public function list($id = null)
    {
        $endpoint = env('ENDPOINT_API');

        if (is_null($id)) {
            $result = $this->client->request('GET', $endpoint . 'content/category');
            if ($result->getStatusCode() != 200) {
                return response()->json([
                    'status' => [
                        'code' => $result->getStatusCode(),
                        'message' => 'Bad Gateway',
                    ]
                ], $result->getStatusCode());
            } else {
                return response()->json(json_decode($result->getBody(), true), 200);
            }
        } else {
            $content = $this->client->request('GET', $endpoint . 'content/metadata');
            if ($content->getStatusCode() != 200) {
                return response()->json([
                    'status' => [
                        'code' => '500',
                        'message' => 'Bad Gateway',
                    ]
                ], 500);
            }
            $data = json_decode($content->getBody(), true);
            $data = ($data['result']);
            $i = 0;
            $kode = null;
            foreach ($data as $d) {
                if ($d['category_id'] == $id) {
                    $kode[$i] = $d;
                    $i++;
                }
            }

            if (is_null($kode)) {
                return response()->json([
                    'status' => [
                        'code' => '404',
                        'message' => 'Not Found',
                    ]
                ], 404);
            }

            return response()->json($kode, 200);
        }
    }

    public function search_videos(Request $request, $genre = null)
    {
        if (is_null($genre)) {
            $aturan = [
                'title' => 'required'
            ];
            $pesan = [
                'required' => 'Please Fill Attribute :attribut'
            ];
            $this->validate($request, $aturan, $pesan);

            $data = $this->client->request('POST', 'http://private-2e0bb9-dbil.apiary-mock.com/content/metadata/search', [
                'query' => urlencode('"video_title=' . $request->title . '"')
            ]);
            if ($data->getStatusCode() != 200) {
                return response()->json([
                    'code' => '500',
                    'message' => 'Bad Gateway'
                ], 500);
            }

            $result = json_decode($data->getBody(), true);

            if ($result['status']['total'] < 1) {
                return response()->json([
                    'code' => '404',
                    'message' => 'Not found'
                ], 404);
            }

            return response()->json($result, 200);
        } else {
            $data = $this->client->request('POST', 'http://private-2e0bb9-dbil.apiary-mock.com/content/metadata/search', [
                'query' => urlencode('"video_genre=' . $genre . '"')
            ]);

            if ($data->getStatusCode() != 200) {
                return response()->json([
                    'code' => '500',
                    'message' => 'Bad Gateway'
                ], 500);
            }

            $result = json_decode($data->getBody(), true);

            if ($result['status']['total'] < 1) {
                return response()->json([
                    'code' => '404',
                    'message' => 'Not found'
                ], 404);
            }

            return response()->json($result, 200);
        }
    }
}
