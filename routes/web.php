<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/leads');

Route::get('/login', fn () => view('auth.login'))->name('login');

Route::get('/leads', fn () => view('leads.index'));
Route::get('/leads/create', fn () => view('leads.create'));
Route::get('/leads/{id}', function (int $id) {
    $reps = User::where('role', UserRole::Rep->value)->orderBy('name')->get();

    return view('leads.show', ['id' => $id, 'reps' => $reps]);
});

Route::get('/reports', fn () => view('reports.index'));
