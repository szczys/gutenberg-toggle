console.log( "I'm loaded!" );

const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;
 
const MyDocumentSettingTest = () => (
        <PluginDocumentSettingPanel className="my-document-setting-plugin" title="My Panel">
        <p>My Document Setting Panel</p>
    </PluginDocumentSettingPanel>
);
 
 registerPlugin( 'document-setting-test', { render: MyDocumentSettingTest } );