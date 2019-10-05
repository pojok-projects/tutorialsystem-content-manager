<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class metadataController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $client, $endpoint;

    public function __construct()
    {
        //
        $this->client = new Client();
        $this->endpoint = env('ENDPOINT');
    }

    private function cekDuplicate($query)
    {
        $result = $this->client->request('POST', $this->endpoint . 'content/metadata/search', [
            'form_params' => [
                'query' => urlencode($query)
            ]
        ]);

        return $this->response_data($result);
    }
    
    private function response_data($result) {
        if($result->getStatusCode() != 200) {
            return response()->json([
                'Message' => 'Bad Gateway',
                'code' => 502
            ], $result->getStatusCode());
        }
    
        return response()->json(json_decode($result->getBody()), $result->getStatusCode());
    }


    private function add_data($id, $param) {
        $result = $this->client->request('GET', $this->endpoint . "content/metadata/$id");
        if($result->getStatusCode() != 200) {
            return response()->json([
                'Message' => 'Bad Gateway'
            ], $result->getStatusCode());
        }
        $data = json_decode($result->getBody(), true);
        $res = $data[$param] + 1;

        $this->client->request('POST', $this->endpoint . "content/metadata/update/$id", [
            'form_params' => [
                $param => $res
            ]
        ]);

        return response()->json([
            'status' => [
                'message' => "$param updated",
                'total' => $res
            ]
            ], 200);
    }

    public function index($id = null)
    {
        if(is_null($id)) {
            $result = $this->client->request('GET', $this->endpoint . 'content/metadata');
    
            return $this->response_data($result);
        } else {
            $result = $this->client->request('GET', $this->endpoint . 'content/metadata/' . $id);
        
            return $this->response_data($result);
        }
    }


    public function store(Request $request)
    {
        $rules = [
            'user_id' => 'required',
            'video_title' => 'required',
            'video_description' => 'required',
            'video_genre' => 'required',
            'privacy' => 'required'
        ];

        $message = [
            'required' => 'Please fill attribute :attribute'
        ];
        $this->validate($request, $rules, $message);

        $result = $this->client->request('POST', $this->endpoint . 'content/metadata/store', [
            'form_params' => [
                'user_id' => $request->user_id,
                'category_id' => 'null',
                'video_title' => $request->video_title,
                'video_description' => $request->video_description,
                'video_genre' => $request->video_genre,
                'video_viewers' => 0,
                'video_share' => 0,
                'video_saves' => 0,
                'video_downloads' => 0,
                'privacy' => $request->privacy,
                'metavideos' => [],
                'subtitle' => [],
                'comments' => [],
                'likes' => [],
                'dislikes' => []
            ]
        ]);

        return $this->response_data($result);
    }

    public function search(Request $request)
    {
        if(isset($request->title) && isset($request->genre)) {
            $rules = [
                'title' => 'required',
                'genre' => 'required'
            ];
            $message = [
                'required' => 'Please Fill Attribute :attribut'
            ];
            $this->validate($request, $rules, $message);

            $query = "video_title=$request->title, video_genre=$request->genre";
            return $this->cekDuplicate($query);
            
        } elseif(isset($request->title) && !isset($request->genre)) {
            $rules = [
                'title' => 'required'
            ];
            $message = [
                'required' => 'Please Fill Attribute :attribut'
            ];
            $this->validate($request, $rules, $message);

            $query = "video_title=$request->title";
            return $this->cekDuplicate($query);
        } elseif(isset($request->genre) && !isset($request->title)) {
            $rules = [
                'genre' => 'required'
            ];
            $message = [
                'required' => 'Please Fill Attribute :attribut'
            ];
            $this->validate($request, $rules, $message);

            $query = "video_genre=$request->genre";
            return $this->cekDuplicate($query);
        } else {
            return response()->json([
                'status' => [
                    'message' => 'Bad Request',
                    'Code' => 400
                ]
                ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $metadata = $this->client->request('GET', $this->endpoint . "content/metadata/$id");
        $metadata = json_decode($metadata->getBody(), true);

        $video_title = isset($request->video_title) ? $request->video_title : $metadata['video_title'];
        $video_description = isset($request->video_description) ? $request->video_description : $metadata['video_description'];
        $video_genre = isset($request->video_genre) ? $request->video_genre : $metadata['video_genre'];
        $privacy = isset($request->privacy) ? $request->privacy : $metadata['privacy'];

        $result = $this->client->request('POST', $this->endpoint . "content/metadata/update/$id", [
            'form_params' => [
                'user_id' => $metadata['user_id'],
                'video_title' => $video_title,
                'video_description' => $video_description,
                'video_genre' => $video_genre,
                'privacy' => $privacy
            ]
        ]);

        return $this->response_data($result);
    }

    public function delete($id)
    {
        $result = $this->client->request('POST', $this->endpoint . "content/metadata/delete/$id");

        return $this->response_data($result);
    }

    public function addDownload($id)
    {
        $param = 'video_downloads';
        return $this->add_data($id, $param);
    }

    public function addViewer($id)
    {
        $param = 'video_viewers';
        return $this->add_data($id, $param);
    }
    
    public function addSave($id)
    {
        $param = 'video_saves';
        return $this->add_data($id, $param);
    }

    public function addShare($id)
    {
        $param = 'video_share';
        return $this->add_data($id, $param);
    }
}
