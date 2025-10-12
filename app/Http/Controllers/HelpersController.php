<?php

namespace App\Http\Controllers;

use Dadata\DadataClient;
use Illuminate\Http\Request;

class HelpersController extends Controller
{
    public function address(Request $request)
    {
        $data = $request->validate([
            'query' => 'required|string|min:2'
        ]);
        $token = config('dadata.token');
        $secret = config('dadata.secret');
        $dadata = new DadataClient($token, $secret);
        $list = $dadata->suggest('address', $data['query'], 10);
        if (empty($list)) {
            return [];
        }
        $result = [];
        foreach ($list as $address) {
            $result[] = [
                'value' => $address['data']['fias_id'],
                'label' => $address['value']
            ];
        }

        return $result;
    }
}
