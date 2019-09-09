<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'WaitListController@index')->name('waitlist');

Route::post('/', 'WaitListController@subscribe');

Route::get('/subscribed', 'WaitListController@subscribed')->name('subscribed');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
