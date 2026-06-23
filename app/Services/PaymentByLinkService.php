<?php

namespace App\Services;

use Illuminate\Http\Request;

interface PaymentByLinkService
{
    public function index(Request $request);
    public function store(Request $request);
    public function show($id);
    public function findByUuid($uuid);
    public function update(Request $request, $id);
    public function destroy($id);
    public function cancel($id);
}
