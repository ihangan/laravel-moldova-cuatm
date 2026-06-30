<?php

declare(strict_types=1);

return [

    /*
     * Table the locations live in. Renamed from a bare "locations" so it does
     * not collide with a table your own application may already own.
     */
    'table' => 'cuatm_locations',

    /*
     * Database connection for the model. Null uses the default connection.
     */
    'connection' => null,

    /*
     * Locales the shipped data carries. Romanian is always present; Russian and
     * Ukrainian come from Wikidata exonyms and exist for most localities;
     * English is only filled in for the larger cities and otherwise falls back
     * to Romanian.
     */
    'locales' => ['ro', 'ru', 'uk', 'en'],

    /*
     * Locale returned when a name is missing in the requested one.
     */
    'fallback_locale' => 'ro',

];
