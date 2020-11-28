//alert(true)

import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { ToggleControl } from "@wordpress/components";
import { withSelect, withDispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";

//Gutenburg toggle
let GutenbergToggle = (props) => {
    return (
        <ToggleControl
        label="Enable Block Editor"
        checked={ props.state }
        onChange={(value) => props.onBlockEditorToggleChange(value)}
        />
    )
}

GutenbergToggle = withSelect(
    (select) => {
        return {
            state: select('core/editor').getEditedPostAttribute('meta')['_use_block_editor']
        }
    }
)(GutenbergToggle);

GutenbergToggle = withDispatch(
    (dispatch) => {
        return {
            onBlockEditorToggleChange: (value) => {
                dispatch('core/editor').editPost({ meta: { _use_block_editor: value } });
            }
        }
    }
)(GutenbergToggle);

registerPlugin('plugin-document-setting-panel-gutenberg-toggle', {
    render: () => {
        return (
        <PluginDocumentSettingPanel
            name="gutenberg-toggle"
            title="Use Block Editor"
            className="gutenberg-toggle"
        >
            <GutenbergToggle />
        </PluginDocumentSettingPanel>
        )
    },
    icon: null
})