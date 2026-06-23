<?php
use \Illuminate\Support\Facades\Route;


Route::get('categories-12', fn($id)=> 'users')->name('categories.ids');
Route::get('about' ,fn()=> 'about')->name('admin.about');
Route::get('support' ,fn()=> 'support')->name('admin.support');
Route::get('activities' ,fn()=> 'activities')->name('admin.activities');
Route::get('contact' ,fn()=> 'contact')->name('admin.contact');
// Route::get('login/12' ,fn()=> 'contact')->name('login');
