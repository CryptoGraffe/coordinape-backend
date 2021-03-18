<?php


namespace App\Repositories;
use App\Models\PendingTokenGift;
use App\Models\TokenGift;
use App\Models\Epoch;
use DB;
use App\Models\User;

class EpochRepository
{
    protected $model;

    public function __construct(PendingTokenGift $model) {
        $this->model = $model;
    }

    public function endEpoch($circle_id) {

        $pending_gifts = $this->model->where(function($q) {
            $q->where('tokens','!=', 0)->orWhere('note','!=','');
        })->get();
        $epoch = Epoch::where('circle_id',$circle_id)->orderBy('id','desc')->first();
        $epoch_number = $epoch ? $epoch->number + 1 : 1;
        DB::transaction(function () use ($pending_gifts, $epoch_number, $circle_id) {

            $epoch = new Epoch(['number'=>$epoch_number, 'circle_id' => $circle_id]);
            $epoch->save();
            foreach($pending_gifts as $gift) {
                $tokenGift = new TokenGift($gift->replicate()->toArray());
                $tokenGift->epoch_id = $epoch->id;
                $tokenGift->save();
            }
            $this->model->where('circle_id',$circle_id)->delete();
            User::where('circle_id',$circle_id)->update(['give_token_received'=>0, 'give_token_remaining'=>100]);
        });
    }

    public function resetGifts($user, $toKeep) {
        $existingGifts = $user->pendingSentGifts()->whereNotIn('recipient_id',$toKeep)->get();
        foreach($existingGifts as $existingGift) {
            $rUser = $existingGift->recipient;
            $existingGift->delete();
            $rUser->give_token_received = $rUser->pendingReceivedGifts()->get()->SUM('tokens');
            $rUser->save();
        }
        $token_used = $user->pendingSentGifts()->get()->SUM('tokens');
        $user->give_token_remaining = 100-$token_used;
        $user->save();
    }

    public function getEpochCsv($epochNumber, $circle_id) {

        $users = User::orderBy('name','asc')->get();
        $header = ['No.','name','address','received','sent','epoch_number'];
        $list = [];
        $list[]= $header;
        $epoch = Epoch::where('number',$epochNumber)->where('circle_id',1)->first();
        foreach($users as $idx=>$user) {
            $col = [];
            $col[] = $idx +1;
            $col[]= $user->name;
            $col[]= $user->address;
            $col[]= $user->receivedGifts()->where('epoch_id',$epoch->id)->where('circle_id',$circle_id)->get()->SUM('tokens');
            $col[]= $user->sentGifts()->where('epoch_id',$epoch->id)->where('circle_id',$circle_id)->get()->SUM('tokens');
            $col[]= $epochNumber;
            $list[]= $col;
        }

        $headers = [
               'Content-type'        => 'text/csv'
           ,   'Content-Disposition' => 'attachment; filename=receipts.csv'
        ];


        $callback = function() use ($list)
        {
            $FH = fopen('php://output', 'w');
            foreach ($list as $row) {
                fputcsv($FH, $row);
            }
            fclose($FH);
        };
        return response()->stream($callback, 200, $headers);
    }
}