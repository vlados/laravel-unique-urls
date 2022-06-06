<?php

Route::get('{urlObj}', [\Vlados\LaravelUniqueUrls\LaravelUniqueUrls::class, 'handleRequest'])->where('urlObj', '.*');
