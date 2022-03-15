<?php

return [
    'plugin' => [
        'name' => 'Páginas',
        'description' => 'Páginas & menus',
    ],
    'page' => [
        'menu_label' => 'Páginas',
        'template_title' => '%s Páginas',
        'delete_confirmation' => 'Estas seguro de querer borrar las páginas seleccionadas? Esto también borrará las sub-páginas que existan.',
        'no_records' => 'No se ha encontrado ninguna página',
        'delete_confirm_single' => 'Estas seguro de querer borrar esta página? Esto también borrará las sub-páginas que existan.',
        'new' => 'Nueva página',
        'add_subpage' => 'Añadir sub-página',
        'invalid_url' => 'Formato de URL no válido. La URL debería comenzar por una barra (\'/\'). Puede contener letras, números, y los siguientes símbolos  _ - / ',
        'url_not_unique' => 'Esta URL ya está siendo utilizada por otra página.',
        'layout' => 'Plantilla',
        'layouts_not_found' => 'No se han encontrado plantillas',
        'saved' => 'La página se ha guardado correctamente.',
        'tab' => 'Páginas',
        'manage_pages' => 'Administrar páginas',
        'manage_menus' => 'Administrar menús',
        'access_snippets' => 'Acceder a fragmentos',
        'manage_content' => 'Administrar contenidos'
    ],
    'menu' => [
        'menu_label' => 'Menus',
        'delete_confirmation' => 'Estas seguro de querer borrar los menus seleccionados?',
        'no_records' => 'No se han encontrado menus.',
        'new' => 'Nuevo menu',
        'new_name' => 'Nuevo menu',
        'new_code' => 'nuevo-menu',
        'delete_confirm_single' => 'Estas seguro de querer borrar este menu?',
        'saved' => 'El menú se ha guardado correctamente.',
        'name' => 'Nombre',
        'code' => 'Código',
        'items' => 'Elementos del menu',
        'add_subitem' => 'Añadir sub-elemento',
        'no_records' => 'No se han encontrado elementos.',
        'code_required' => 'El código es obligatorio',
        'invalid_code' => 'El formato del código no es válido. Puede contener letras, números y los siguientes símbolos: _ - '
    ],
    'menuitem' => [
        'title' => 'Título',
        'editor_title' => 'Editar elemento del menu',
        'type' => 'Tipo',
        'allow_nested_items' => 'Permitir elementos anidados',
        'allow_nested_items_comment' => 'Los elementos anidados se pueden generar automáticamente mediante las páginas y otros tipos de elementos.',
        'url' => 'URL',
        'reference' => 'Referencia',
        'title_required' => 'El título es obligatorio',
        'unknown_type' => 'Este tipo de elemento del menú es desconocido.',
        'unnamed' => 'Elemento del menú sin nombre',
        'add_item' => 'Añadir elemento',
        'new_item' => 'Nuevo elemento',
        'replace' => 'Sustituye este elemento por los sub-elementos que contenga.',
        'replace_comment' => 'Marca esta casilla sustituir este elemento por los sub-elementos que contenga. El elemento proncipal quedará oculto.',
        'cms_page' => 'Página del CMS',
        'cms_page_comment' => 'Selecciona una página a la que enlazar cuando se haga click en este elemento del menu.',
        'reference_required' => 'La referencia al elemento del menú es obligatoria.',
        'url_required' => 'La URL es obligatoria',
        'cms_page_required' => 'Selecciona una página del CMS',
        'code' => 'Código',
        'code_comment' => 'Introduce el código del elemento para acceder mediante la API.'
    ],
    'content' => [
        'menu_label' => 'Contenido',
        'cant_save_to_dir' => 'No está permitido guardar archivos de contenido en el directorio de las páginas.'
    ],
    'sidebar' => [
        'add' => 'Añadir',
        'search' => 'Buscar...'
    ],
    'object' => [
        'invalid_type' => 'Tipo de objeto desconocido',
        'not_found' => 'No se ha encontrado el objeto solicitado.'
    ],
    'editor' => [
        'title' => 'Título',
        'new_title' => 'Título de la nueva página',
        'content' => 'Contenido',
        'url' => 'URL',
        'filename' => 'Nombre de archivo',
        'layout' => 'Plantilla',
        'description' => 'Descripción',
        'preview' => 'Vista previa',
        'duplicate' => 'Duplicado',
        'enter_fullscreen' => 'Entrar en modo de pantalla completa',
        'exit_fullscreen' => 'Salir del modo de pantalla completa',
        'hidden' => 'Oculto',
        'hidden_comment' => 'Las páginas ocultas solo son visibles para los administradores que hayan iniciado sesión.',
        'navigation_hidden' => 'No mostrar en el menu',
        'navigation_hidden_comment' => 'Marca esta casilla para ocultar esta página en los menus generados automáticamente.',
    ],
    'snippet' => [
        'partialtab' => 'Fragmentos',
        'code' => 'Código del fragmento',
        'code_comment' => 'Introduce un código para hacer que el fragmento esté disponible en el plugin de páginas.',
        'name' => 'Nombre',
        'name_comment' => 'El nombre se muestra en la lista de fragmentos, en el menu lateral del plugin de las páginas, y en cada página en la que se haya utilizado el fragmento.',
        'no_records' => 'No se han encontrado fragmentos',
        'menu_label' => 'Fragmentos',
        'column_property' => 'Título de propiedad',
        'column_type' => 'Tipo',
        'column_code' => 'Código',
        'column_default' => 'Valor Predeterminado',
        'column_options' => 'Opciones',
        'column_type_string' => 'Texto',
        'column_type_checkbox' => 'Casilla',
        'column_type_dropdown' => 'Desplegable',
        'not_found' => 'No se ha encontrado ningún fragmento con el código :code en este tema.',
        'property_format_error' => 'El código de propiedad debería comenzar por una letra. Sólo puede contener letras y números.',
        'invalid_option_key' => 'La clave de opción del desplegable no es válida : %s. Estas claves sólo pueden contener números, letras y los símbolos _ y -'
    ]
];
