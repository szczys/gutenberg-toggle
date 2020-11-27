//alert(true)

import { registerPlugin } from "@wordpress/plugins";
import { PluginSidebar } from "@wordpress/edit-post";
import { TextControl, ToggleControl } from "@wordpress/components";
import { withState } from '@wordpress/compose';
import { withSelect, withDispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";

registerPlugin( 'myprefix-sidebar', {
    icon: 'smiley',
    render: () => {
      return (
        <>
          <PluginSidebar
            title={__('Meta Options', 'textdomain')}
          >
            Some Content
          </PluginSidebar>
        </>
      )
    }
  })

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
            onBlockEditorToggleChange: function (value) {
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


// const { registerPlugin } = wp.plugins
import { RadioControl } from '@wordpress/components';
const { PluginDocumentSettingPanel } = wp.editPost

let SubtitleControl = ({ subtitle, handleSubtitleChange }) => (
    <TextControl
        label="Set subtitle"
        value={subtitle}
        onChange={subtitle => handleSubtitleChange(subtitle)}
    />
);

SubtitleControl = withSelect(
    (select) => {
        return {
            subtitle: select('core/editor').getEditedPostAttribute('meta')['customname_meta_subtitle']
        }
    }
)(SubtitleControl);

SubtitleControl = withDispatch(
    (dispatch) => {
        return {
            handleSubtitleChange: (value) => {
                dispatch('core/editor').editPost({ meta: { customname_meta_subtitle: value } })
            }
        }
    }
)(SubtitleControl);

let HeaderImageHeightControl = ({ height, handleHeightChange }) => (
    <RadioControl
        label="Set image height"
        help="Set the height of the header image"
        selected={height}
        options={[
            { label: '100', value: '1' },
            { label: '200', value: '2' },
        ]}
        onChange={handleHeightChange}
    />
);

HeaderImageHeightControl = withSelect(
    (select) => {
        return {
            height: select('core/editor').getEditedPostAttribute('meta')['customname_meta_header_height']
        }
    }
)(HeaderImageHeightControl);

HeaderImageHeightControl = withDispatch(
    (dispatch) => {
        return {
            handleHeightChange: value => {
                dispatch('core/editor').editPost({ meta: { customname_meta_header_height: value } })
            }
        }
    }
)(HeaderImageHeightControl);

const PluginDocumentSettingPanelDemo = () => (
    <PluginDocumentSettingPanel
        name="custom-panel"
        title="Custom Panel"
        className="custom-panel"
    >
        <SubtitleControl />
        <HeaderImageHeightControl />
        <MyMediaUploader />
    </PluginDocumentSettingPanel>
)
registerPlugin('plugin-document-setting-panel-demo', {
    render: PluginDocumentSettingPanelDemo
})

import { ToolbarButton } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

const ALLOWED_MEDIA_TYPES = [ 'image' ];

let MyMediaUploader = ({ mediaId, handleSubtitleChange }) => (
//function MyMediaUploader() {
	//return (
		<MediaUploadCheck>
			<MediaUpload
				onSelect={ ( media ) => console.log( 'selected ' + media.length ) }
				allowedTypes={ ALLOWED_MEDIA_TYPES }
				value={ mediaId }
				render={ ( { open } ) => (
					<ToolbarButton onClick={ open }>
						Open Media Library
					</ToolbarButton>
				) }
			/>
		</MediaUploadCheck>
	);
//}