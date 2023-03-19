import PreviewWorkflowSettings from './components/PreviewWorkflowSettings.vue'

OCA.WorkflowEngine.registerOperator({
	id: 'OCA\\PyPreview\\Operation\\PreviewOperation',
	operation: 'small;medium;large',
	options: PreviewWorkflowSettings,
	color: '#f0ad4e',
})
