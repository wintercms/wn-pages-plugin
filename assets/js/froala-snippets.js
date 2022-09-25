(function ($) {
    // Adds snippets to the default Froala buttons
    // Places it after the quote button to keep dropdowns together
    $.wn.richEditorButtons.splice(3, 0, 'snippets');

    // Define a dropdown button.
    $.FroalaEditor.RegisterCommand('snippets', {
        // Button title.
        title: 'Snippets',

        // Mark the button as a dropdown.
        type: 'dropdown',

        // Specify the icon for the button.
        // If this option is not specified, the button name will be used.
        icon: '<i class="icon-newspaper-o"></i>',

        // The dropdown HTML
        html: function() {
            if (!$.wn.snippets) {
                return '<div style="padding:10px;">No snippets are currently defined.</div>';
            }

            var html = '<ul class="fr-dropdown-list">';

            $.each($.wn.snippets, function(i, snippet) {
                html += '<li><a class="fr-command" data-cmd="snippets" data-param1="' + snippet.snippet + '" title="' + snippet.name + '">' + snippet.name + '</a></li>';
            });

            return html + '</ul>';
        },

        // Save the dropdown action into undo stack.
        undo: true,

        // Focus inside the editor before callback.
        focus: true,

        // Refresh the button state after the callback.
        refreshAfterCallback: true,

        // Callback.
        callback: function (cmd, val, params) {
            var options = $.wn.snippets[val];

            if (options) {
                // Get editor element. OC2's richeditor has 2 nested data-control=richeditor, we want the outer one
                var $editor = this.$el.parents('[data-control="richeditor"]:not([data-richeditor-vue])');

                var $snippetNode = $('<figure contenteditable="false" data-inspector-css-class="hero">&nbsp;</figure>');

                if (options.component) {
                    $snippetNode.attr({
                        'data-component': options.component,
                        'data-inspector-class': options.component
                    })

                    // If a component-based snippet was added, make sure that
                    // its code is unique, as it will be used as a component
                    // alias.

                    /*
                    // Init a new snippet manager

                    // Below code reattaches the inspector event, causing duplicate inspector options
                    // Until I can figure a solution, I have copied the code to this file...

                    var snippetManager = new $.wn.pages.snippetManager;
                    options.snippet = snippetManager.generateUniqueComponentSnippetCode(options.component, options.snippet, $editor.parent())
                    */

                    options.snippet = generateUniqueComponentSnippetCode(options.component, options.snippet, $editor.parent());
                }

                $snippetNode.attr({
                    'data-snippet': options.snippet,
                    'data-name': options.name,
                    'tabindex': '0',
                    'draggable': 'true',
                    'data-ui-block': 'true'
                })

                $snippetNode.addClass('fr-draggable');

                // Insert the content
                this.figures.insert($snippetNode);
            }

        }
    });

    generateUniqueComponentSnippetCode = function(componentClass, originalCode, $pageForm) {
        var updatedCode = originalCode,
            counter = 1,
            snippetFound = false

        do {
            snippetFound = false

            $('[data-control="richeditor"] textarea', $pageForm).each(function() {
                var $textarea = $(this),
                    $codeDom = $('<div>' + $textarea.val() + '</div>')

                if ($codeDom.find('[data-snippet="'+updatedCode+'"][data-component]').length > 0) {
                    snippetFound = true
                    updatedCode = originalCode + counter
                    counter++

                    return false
                }
            })

        } while (snippetFound)

        return updatedCode
    };


    /**
     * Because the pages-snippets.js is injected after the richeditor script, it will register its
     * initialization hooks too late. Here we need to force initialization in order to work with forms
     * that are displayed on page load (i.e. Winter.Blog).
     */
    $(document).ready(function() {
        var $editor = $('[data-control="richeditor"]:not([data-richeditor-vue])');

        if ($.wn.pagesPage && !window.location.pathname.includes('winter/pages')) {
            $.wn.pagesPage.snippetManager.initSnippets($editor);
        }
    });

})(jQuery);