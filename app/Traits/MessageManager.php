<?php

namespace App\Traits;

trait MessageManager
{
    public function SuccessMessage($message):void
    {
        //        Laravel Notification Message
        flash()
            ->options([
                'timeout' => 3000, // 3 seconds
                'position' => 'bottom-right',
            ])
            ->success($message);
        //        Laravel Session
        // session()->flash('success',$message );
    }

    public function ErrorMessage($message):void {
        //        Laravel Notification Message
        flash()
            ->options([
                'timeout' => 3000, // 3 seconds
                'position' => 'bottom-right',
            ])
            ->error($message);
        //        Laravel Session
        // session()->flash('error',$message );
    }
}
