<template>
    <select class="form-control" :class="{'input-lg': size === 'lg', 'input-sm': size === 'sm'}"><slot></slot></select>
</template>

<script>
    export default {
        props: ['url', 'value', 'size'],
        mounted() {
            let vm = this;
            $(this.$el)
                // init select2
                .select2(this.getOptions())
                .val(this.value)
                .trigger('change')
                // emit event on change.
                .on('change', function () {
                    vm.$emit('input', this.value)
                })
        },
        watch: {
            value: function (value) {
                // update value
                $(this.$el)
                    .val(value)
                    .trigger('change');

                this.$emit('select-change', value);
            },
            url: function (url) {
                // update options
                $(this.$el).empty().select2({ data: this.getOptions() })
            }
        },
        destroyed: function () {
            $(this.$el).off().select2('destroy')
        },
        methods: {
            getOptions() {
                return {
                    theme: "bootstrap",
                    placeholder: "Search by member id, site name, first name or last name",
                    allowClear: true,
                    minimumInputLength: 1,
                    width: "100%",
                    ajax: {
                        url:  this.url,
                        dataType: 'json',
                        type: "GET",
                        //delay: 250,
                        data(params) {
                            return params;
                        },
                        processResults (data) {
                            return data;
                        },
                    },
                    containerCssClass: ':all:'
                }
            },
            setValue(text, id) {
                let option = new Option(text, id, true, true);

                $(this.$el).val(null).trigger('change')
                $(this.$el).append(option).trigger('change');

                // manually trigger the `select2:select` event
                $(this.$el).trigger({
                    type: 'select2:select',
                    params: {
                        data: {
                            text: text,
                            id: id,
                        }
                    }
                });

                this.$emit('select-change', id);
            },
            setDisabled(disable) {
                $(this.$el).prop("disabled", disable)
            },
        }
    }
</script>
