Ext.onReady(function () {
	var mainPanel = Ext.getCmp("modx-panel-resource");
	if (!mainPanel) return;

	if (mainPanel.config.record.id > 0) {
		FileAttach.config.docid = mainPanel.config.record.id;

		MODx.addTab("modx-resource-tabs", {
			cls: 'x-grid-panel',
			title: _("files"),
			width: '95%',
			forceLayout: true,
			items: [{
				xtype: 'modx-vtabs',
				anchor: '100%',
				items: [{
					cls: 'main-wrapper',
					title: 'Список файлов',
					anchor: '100%',
					items: [{
						width: '95%',
						xtype: "fileattach-grid-items",
					}]
				}, {
					title: 'Архив'
				}]
			}]
		});
	}
});
