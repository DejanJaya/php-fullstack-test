<?php

namespace App\Http\Controllers;

    use App\Models\MyClient;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Redis;
    
    class MyClientController extends Controller
    {
        public function index()
        {
            return MyClient::all();
        }
    
        public function store(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:250',
                'slug' => 'required|string|max:100|unique:my_client',
                'client_logo' => 'required|image|max:2048',
                // tambahkan validasi lain sesuai kebutuhan
            ]);
    
            $clientLogoPath = $request->file('client_logo')->store('logos', 's3');
    
            $client = MyClient::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'client_logo' => $clientLogoPath,
                // set field lain
            ]);
    
            Redis::set($client->slug, json_encode($client));
    
            return response()->json($client, 201);
        }
    
        public function show($slug)
        {
            $client = Redis::get($slug);
            if ($client) {
                return json_decode($client);
            }
    
            return MyClient::where('slug', $slug)->firstOrFail();
        }
    
        public function update(Request $request, $slug)
        {
            $client = MyClient::where('slug', $slug)->firstOrFail();
    
            $request->validate([
                'name' => 'sometimes|required|string|max:250',
                'client_logo' => 'sometimes|image|max:2048',
                // tambahkan validasi lain sesuai kebutuhan
            ]);
    
            if ($request->hasFile('client_logo')) {
                Storage::disk('s3')->delete($client->client_logo);
                $clientLogoPath = $request->file('client_logo')->store('logos', 's3');
                $client->client_logo = $clientLogoPath;
            }
    
            $client->update($request->all());
            Redis::set($client->slug, json_encode($client));
    
            return response()->json($client);
        }
    
        public function destroy($slug)
        {
            $client = MyClient::where('slug', $slug)->firstOrFail();
            $client->delete();
            Redis::del($slug);
    
            return response()->json(null, 204);
        }
    }

