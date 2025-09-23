<?php

use App\Http\Controllers\CredentialController;
use Illuminate\Support\Facades\Route;

Route::get('test', function () {

});


Route::get('/', [CredentialController::class, 'index'])->name('credentials.index');
Route::get('/vista', [CredentialController::class, 'vista'])->name('credentials.vista');
Route::get('/contacts', [CredentialController::class, 'contacts'])->name('credentials.contacts');

Route::post('/extract', [CredentialController::class, 'extract'])->name('credentials.extract');
Route::post('/store', [CredentialController::class, 'store'])->name('credentials.store');
