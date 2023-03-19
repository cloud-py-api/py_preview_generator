<template>
	<div class="py_preview_workflow_settings">
		<NcMultiselect :value="currentValue"
			:options="options"
			track-by="id"
			label="text"
			@input="(newValue) => newValue !== null && $emit('input', newValue.id)" />
	</div>
</template>

<script>
import NcMultiselect from '@nextcloud/vue/dist/Components/NcMultiselect.js'

const previewSizeOptions = [
	{
		id: 'small;medium;large',
		text: t('py_preview_generator', 'Small, Medium and Large'),
	},
	{
		id: 'medium;large',
		text: t('py_preview_generator', 'Medium and Large'),
	},
	{
		id: 'small;large',
		text: t('py_preview_generator', 'Small and Large'),
	},
	{
		id: 'small;medium',
		text: t('py_preview_generator', 'Small and Medium'),
	},
	{
		id: 'small',
		text: t('py_preview_generator', 'Small'),
	},
	{
		id: 'medium',
		text: t('py_preview_generator', 'Medium'),
	},
	{
		id: 'large',
		text: t('py_preview_generator', 'Large'),
	},
]
export default {
	name: 'PreviewWorkflowSettings',

	components: {
		NcMultiselect,
	},

	props: {
		value: {
			default: previewSizeOptions[0],
			type: String,
		},
	},

	data() {
		return {
			options: previewSizeOptions,
		}
	},

	computed: {
		currentValue() {
			const newValue = previewSizeOptions.find(option => option.id === this.value)
			if (typeof newValue === 'undefined') {
				return previewSizeOptions[0]
			}
			return newValue
		},
	},

	watch: {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
	.multiselect {
		width: 100%;
		margin: auto;
		text-align: center;
	}
</style>
