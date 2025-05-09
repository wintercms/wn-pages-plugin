<?php

return [
    'plugin' => [
        'name' => 'Sidor',
        'description' => 'Sidor & menyer.',
    ],
    'page' => [
        'menu_label' => 'Sidor',
        'template_title' => '%s Sidor',
        'delete_confirmation' => 'Vill du verkligen ta bort de valda sidorna? Detta kommer även ta bort undersidorna, om det finns några.',
        'no_records' => 'Inga sidor hittades',
        'delete_confirm_single' => 'Vill du verkligen ta bort den valda sidan? Detta kommer även ta bort sidans undersidor, om det finns några.',
        'new' => 'Ny sida',
        'add_subpage' => 'Lägg till undersida',
        'invalid_url' => 'Ogiltigt format på URL. URL:en ska börja med slash och kan innehålla siffror, latinska bokstäver och följande symboler: _-/',
        'url_not_unique' => 'Denna URL används redan av en annan sida.',
        'layout' => 'Layout',
        'layouts_not_found' => 'Layouter kan inte hittas',
        'saved' => 'Sidan har sparats.',
        'tab' => 'Sidor',
        'manage_pages' => 'Hantera statiska sidor',
        'manage_menus' => 'Hantera statiska menyer',
        'access_snippets' => 'Hantera stumpar',
        'manage_content' => 'Hantera statiskt innehåll',
    ],
    'menu' => [
        'menu_label' => 'Menyer',
        'delete_confirmation' => 'Vill du verkligen ta bort valda de menyerna?',
        'no_records' => 'Inga menyer hittades',
        'new' => 'Ny meny',
        'new_name' => 'Ny meny',
        'new_code' => 'ny-meny',
        'delete_confirm_single' => 'Vill du verkligen ta bort denna meny?',
        'saved' => 'Menyn har sparats.',
        'name' => 'Namn',
        'code' => 'Kod',
        'items' => 'Menyobjekt',
        'add_subitem' => 'Lägg till undermeny',
        'no_records' => 'Inga föremål kunde hittas',
        'code_required' => 'Koden är obligatorisk',
        'invalid_code' => 'Ogiltigt kodformat. Koden kan innehålla siffror, latinska bokstäver och följande symboler: _-',
    ],
    'menuitem' => [
        'title' => 'Titel',
        'editor_title' => 'Redigera menyobjekt',
        'type' => 'Typ',
        'allow_nested_items' => 'Tillåt underliggande menyer',
        'allow_nested_items_comment' => 'Underliggande menyer kan skapas dynamiskt av en statisk sida och några andra föremålstyper',
        'url' => 'URL',
        'reference' => 'Referens',
        'title_required' => 'Titel är obligatorisk',
        'unknown_type' => 'Okänd menytyp',
        'unnamed' => 'Meny utan namn',
        'add_item' => 'Lägg till <u>M</u>enyobjekt',
        'new_item' => 'Ny meny',
        'replace' => 'Ersätt menyobjekt med skapade underliggande menyer',
        'replace_comment' => 'Använd denna kryssruta för att flytta skapade menyer till samma nivå som detta menyobjekt. Detta menyobjekt kommer att gömmas.',
        'cms_page' => 'CMS-sida',
        'cms_page_comment' => 'Välj sida som ska öppnas när menyn klickas på.',
        'reference_required' => 'Menyreferens är obligatorisk.',
        'url_required' => 'URL:en är obligatorisk',
        'cms_page_required' => 'Vänligen välj en CMS-sida',
        'code' => 'Kod',
        'code_comment' => 'Ange kod om du vill få tillgång till menyobjektet i API:t.',
    ],
    'content' => [
        'menu_label' => 'Innehåll',
        'cant_save_to_dir' => 'Att spara innehållsfiler till mappen för statiska sidor är inte tillåtet.',
    ],
    'sidebar' => [
        'add' => 'Lägg till',
        'search' => 'Sök...',
    ],
    'object' => [
        'invalid_type' => 'Ogiltig objekttyp',
        'not_found' => 'Det begärda objektet kunde inte finnas.',
    ],
    'editor' => [
        'title' => 'Titel',
        'new_title' => 'Ny sidtitel',
        'content' => 'Innehåll',
        'url' => 'URL',
        'filename' => 'Filnamn',
        'layout' => 'Layout',
        'description' => 'Beskrivning',
        'preview' => 'Förhandsgranska',
        'enter_fullscreen' => 'Gå in i fullskärmsläge',
        'exit_fullscreen' => 'Gå ut ur fullskärmsläge',
        'hidden' => 'Gömd',
        'hidden_comment' => 'Gömda sidor är bara tillgängliga för inloggande back-endanvändare.',
        'navigation_hidden' => 'Göm i navigation',
        'navigation_hidden_comment' => 'Fyll i denna rutan för att gömma sidan från automatiskt skapade menyer och sökvägar.',
    ],
    'snippet' => [
        'partialtab' => 'Stump',
        'code' => 'Stumpkod',
        'code_comment' => 'Skriv in en kod som gör att denna sidurklipp är tillgänglig som en stump i tillägget för statiska sidor.',
        'name' => 'Namn',
        'name_comment' => 'Namnet visas i listan med stumpar i sidopanelen och på en sida när stumpen är tillagd.',
        'no_records' => 'Inga stumpar hittades',
        'menu_label' => 'Stumpar',
        'column_property' => 'Egenskapsrubrik',
        'column_type' => 'Typ',
        'column_code' => 'Kod',
        'column_default' => 'Standard',
        'column_options' => 'Alternativ',
        'column_type_string' => 'Sträng',
        'column_type_checkbox' => 'Kryssruta',
        'column_type_dropdown' => 'Rullgardinsmeny',
        'not_found' => 'En stump med den begärda koden :code kunde inte hittas i temat.',
        'property_format_error' => 'Egenskapskoden ska börja med en latisk bokstav kan bara innehålla latinska bokstäver samt siffror',
        'invalid_option_key' => 'Nyckeln: %s, i Rullgardinsmenyn är ogiltig. Alternativnycklarna kan bara innehålla siffror, latinska bokstäver och karaktärerna _ samt -',
    ],
];
