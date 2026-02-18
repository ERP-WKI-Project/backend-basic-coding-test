<?php

use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json('Welcome to  ERP WIKI API! - Bastian'))->name('welcome');

// Scramble Back Office API Documentation Routes
Scramble::registerUiRoute(path: 'docs/backoffice', api: 'back-office')
    ->name('scramble.back-office.docs.ui');

Scramble::registerJsonSpecificationRoute(path: 'docs/backoffice.json', api: 'back-office')
    ->name('scramble.back-office.docs.document');

// Scramble Machine API Documentation Routes
Scramble::registerUiRoute(path: 'docs/machine', api: 'machine')
    ->name('scramble.machine.docs.ui');

Scramble::registerJsonSpecificationRoute(path: 'docs/machine.json', api: 'machine')
    ->name('scramble.machine.docs.document');

