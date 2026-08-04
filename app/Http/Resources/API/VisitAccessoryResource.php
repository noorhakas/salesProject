<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class VisitAccessoryResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
{
    $url = 'https://fls-a259fcee-b4fb-4b36-a088-479046f36e09.laravel.cloud';

    $image = match ((int) $this->type) {
        0       => $this->file != '' ? $url . '/products/' . $this->file : url('/') . '/assets/img/medicine_logo.png',
        3       => $this->file != '' ? $url . '/product_files/' . $this->file : '',
        default => '',
    };

    $base = [
        'id'               => $this->id,
        'item_name'        => $this->name,
        'image'            => $image,
        'count_of_sample'  => $this->count_of_sample,
        'checked'          => $this->checked,
        'type'             => $this->type,
    ];

    if ((int) $this->type === 0) {
        $base = array_merge($base, ['price' => $this->price]);
    }

    return $base;
}


   
}
