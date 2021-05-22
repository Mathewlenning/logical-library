if(typeof logical === 'undefined')
{
    logical = {};
}

logical.list =
{
    checkKey: function(e)
    {
        var key = e.which || e.keyCode;

        if(key == '13')
        {
            // Return button
            logical.list.addItem(e);
            logical.list.focusLastInput(e);

            e.preventDefault();
            return false;
        }

        if(key == '46')
        {
            // Delete button
            logical.list.removeMe(e);
            logical.list.focusLastInput(e);
        }
    },

    addItem:function(e)
    {
        var targ = e.target || e.srcElement;
        targ = jQuery(targ);
        var parent = targ.parent('li');

        var list = targ.closest('ul');
        var lastLi = jQuery(list.prop('lastElementChild'));

        if(parent.is(lastLi) && targ.val().trim().length != 0)
        {
            var clone = targ.clone();
            parent.after('<li><i class="icon-blank"></i>'+clone[0].outerHTML+'</li>');

            var icon = jQuery(parent.children('i'));
            icon.removeClass('icon-blank').addClass('js-sort-list').addClass('icon-menu-2')
        }
    },

    focusLastInput:function(e)
    {
        var targ = e.target || e.srcElement;
        targ = jQuery(targ);

        var lastInput = jQuery('input[name = "'+targ.prop('name')+'"]:last');
        lastInput.focus();
    },

    removeMe:function (e)
    {
        var targ = e.target || e.srcElement;
        var parent = jQuery(targ.parentElement);
        var list = jQuery(targ.parentElement.parentElement);

        if(list.prop('childElementCount') == 1)
        {
            targ = jQuery(targ);
            var clone = targ.clone();
            parent.after('<li><i class="icon-blank"></i>'+clone[0].outerHTML+'</li>');
        }

        parent.remove();
    },

    addSorting:function()
    {
        var list = jQuery('.js-sort-list');
        list.sortable(
            {
                handle:'.js-handle',
                axis:'y'
            }
        );
    }
};
