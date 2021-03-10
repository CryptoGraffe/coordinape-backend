<?php

namespace App\Http\Requests;

use Ethereum\EcRecover;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use App\Models\User;
use App\Helper\Utils;

class GiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $data = $this->get('data');
        $signature = $this->get('signature');
        $address  = strtolower($this->get('address'));
        $recoveredAddress = Utils::personalEcRecover($data,$signature);
        $is_user = User::byAddress($address)->first();
        return $is_user && strtolower($recoveredAddress)==$address;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'gifts' => json_decode($this->get('data'), true)
        ]);
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'data' => 'required',
            'signature' => 'required',
            'address' => 'required',
            'gifts.*.recipient_address' => 'required|string|size:42',
            'gifts.*.tokens' => 'required|integer|max:100'
        ];

        $gifts = $this->gifts;
        if(!$gifts)
            throw new ConflictHttpException('data cannot be null');

        $sum = array_reduce($gifts, function($carry, $item)
        {
            return $carry + $item['tokens'];
        });

        $user = User::byAddress($this->address)->first();
        if(!$user)
            throw new ConflictHttpException('User cannot be found');

        $this->merge(['user' => $user]);

        if($sum > 100) {
            throw new ConflictHttpException('Sum of tokens is more than 100');
        }

        return $rules;
    }
}