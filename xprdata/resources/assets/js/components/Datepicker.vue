<template>
    <input type="text"
           class="form-control"
           v-bind:value="value"
    />
</template>

<script>
    export default {
        props: ['options', 'value', 'startDate', 'endDate'],
        mounted: function () {

            $.fn._datepicker = jQuery.fn.datepicker;

            let vm = this;

            jQuery(this.$el)
                ._datepicker({...this.options, format: "yyyy-mm-dd"})
                ._datepicker('setStartDate', this.toDate(this.startDate))
                .on('changeDate',  (e) => {
                    vm.$emit('input', moment(e.date).format("YYYY-MM-DD"));
                });

            jQuery(this.$el)._datepicker('setDate', new Date());

            if(typeof this.endDate !== "undefined") {
                jQuery(this.$el)._datepicker('setEndDate', this.toDate(this.endDate))
            }

        },
        methods: {
            toDate(date) {

                if(date === undefined) return false;

                return moment(date, "YYYY-MM-DD").toDate();
            },
        },
        watch: {
            options(options) {
                jQuery(this.$el)._datepicker('destroy');
                jQuery(this.$el).empty()._datepicker({...this.options, format: "yyyy-mm-dd"});
            },
            startDate(startDate) {

                jQuery(this.$el)._datepicker('setStartDate', this.toDate(startDate));

                if(jQuery(this.$el)._datepicker('getDate') == null || jQuery(this.$el)._datepicker('getDate') < this.toDate(startDate)) {
                    jQuery(this.$el)._datepicker('setDate', this.toDate(startDate));
                }

            },
            endDate(endDate) {
                jQuery(this.$el)._datepicker('setEndDate', this.toDate(endDate));
            },
            value (value) {

                if(moment(value).isValid()) {
                    jQuery(this.$el)._datepicker('setDate', moment(value).toDate())
                } else {
                    jQuery(this.$el)._datepicker('setDate', new Date());
                }

            }
        },
        destroyed: function () {
            jQuery(this.$el).off()._datepicker('destroy')
        }
    }
</script>
