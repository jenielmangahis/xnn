(function ($, api_url, Vue, swal, axios, location, moment,undefined) {
	$.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

	const client = commissionEngine.createAccessClient();
	commissionEngine.setupAccessTokenJQueryAjax();
	const lang = cookieValue = document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
    $.fn._datepicker = jQuery.fn.datepicker;

    
    if(lang == 'english'){
        $.fn._datepicker.dates['en'] = {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
            daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
            months: ["Januarys", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            today: "Today",
            clear: "Clear",
            format: "YYYY-MM-DD",
            titleFormat: "MM yyyy", /* Leverages same syntax as 'format' */
            weekStart: 0
    };
     }
    else{
        $.fn._datepicker.dates['en'] = {
            days: ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"],
            daysShort: ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"],
            daysMin: ["Do", "Lu", "Ma", "Me", "Gi", "Ve", "Sa"],
            months: ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"],
            monthsShort: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
            today: "Oggi",
            monthsTitle: "Mesi",
            clear: "Cancella",
            format: "YYYY-MM-DD",
            titleFormat: "MM yyyy", /* Leverages same syntax as 'format' */
            weekStart: 0
    };
    
    }


	const vm = new Vue({
		el: ".tool-container",
		data: {
			dt: null,

			frequencies: [],
			frequencyState: "loaded", // loaded/fetching/error
			frequency: "",

			commissionPeriods: [],
			commissionPeriodState: "", // loaded/fetching/error
			commissionPeriodIndex: "",

			commissionTypes: [],
			commissionType: "",

			downloadLink: "",
			downloadLinkState: "loaded",

			filters: {
				period: {
					start_date: moment().format("YYYY-MM-DD"),
					end_date: moment().format("YYYY-MM-DD"),
				},
				start_date: moment().format("YYYY-MM-DD"),
				end_date: moment().format("YYYY-MM-DD"),
				commission_type_id: null,
				frequency: null,
				language:  null,
			},
			today: moment().format("YYYY-MM-DD"),
			dtCount: 0,
		},
		mounted() {
			this.getFrequencies();
			this.initializeDataTables();
			this.initializeJQueryEvents();
		},
		methods: {
			initializeDataTables() {
				let _this = this;
				this.dt = $("#detailed_commission").DataTable({
					// searching: false,
					// lengthChange: true,
					processing: true,
					serverSide: true,
					responsive: true,
					ajax: {
						url: `${api_url}member/detailed-commission`,
						data: function (d) {
							d.start_date = _this.filters.start_date;
							d.end_date = _this.filters.end_date;
							d.commission_type_id = _this.filters.commission_type_id;
							d.language = lang;
						},
						"dataSrc" : function(res){
							_this.dtCount = res.data.length;
							return res.data;
						}
					},
					order: [[0, 'asc']],
					columns: [
						{data: 'group_name'},
						{data: 'type_name'},
						{data: 'level'},
						{data: 'associates_enroller'},
						{data: 'associate_id'},
						{data: 'customer_name'},
						{data: 'pod_pdr'},
						{data: 'type'},
						{data: 'gross_amount', className: "text-center"},
						{data: 'date_enrolled'},
						{data: 'date_accepted'},
						{data: 'receipt_num', className: "text-center"},
					]
				});
			},
			initializeJQueryEvents(){
				let _this = this;
				$(".dropdown-menu a").on('click', function(event){
					event.preventDefault();
					_this.filters.language = $(this).attr('data-lang');
				});
			},
			getFrequencies() {

				if (this.frequencyState === "fetching") return;

				this.frequencyState = "fetching";
				this.frequencies = [];
				this.frequency = "";
				this.commissionTypes = [];

				client.get("common/commission-types/group",)
					.then(response => {
						this.frequencies = response.data;
						this.frequencyState = "loaded";

					})
					.catch(error => {
						this.frequencyState = "error";
					})
			},
			onChange:function(event) {

				if (this.commissionPeriodState === "fetching") return;

				this.commissionPeriodState = "fetching";
				this.commissionPeriods = [];
				this.commissionPeriodIndex = "";

				if (this.frequency === "all") {
                    setTimeout(() => this.commissionPeriodState = "all", 1300);
                    setTimeout(() => this.commissionPeriodIndex = "all", 1300);
					return;
				}

				client.get("common/commission-types/group-types", {
					params: {
						frequency: this.frequency,
						language: lang
					}
				})
					.then(response => {
						this.commissionPeriods = response.data;
						this.commissionPeriodState = "loaded";

					})
					.catch(error => {
						this.commissionPeriodState = "error";
					})

			},
			view() {

				if (!this.commissionPeriodIndex) return;

				// console.log(this.commissionPeriodIndex);
				this.filters.start_date = this.filters.period.start_date;
				this.filters.end_date = this.filters.period.end_date;
				this.filters.commission_type_id = this.commissionPeriodIndex === "all" ? 0 : this.commissionPeriodIndex;
				this.dt.clear().draw();
				this.dt.responsive.recalc();
			},
			getDownloadLink() {
				if (this.downloadLinkState === "fetching") return;

				this.downloadLinkState = "fetching";
				this.downloadLink = "";

				client.get("member/detailed-commission/download", {
					params: this.filters
				})
					.then(response => {
						this.downloadLinkState = "loaded";
						this.downloadLink = response.data.link;

						if (!!this.downloadLink) {
							window.location = this.downloadLink;
						}
					})
					.catch(error => {
						this.downloadLinkState = "error";
					})
			},
		}
	});

	window.onbeforeunload = function () {
		if (vm.downloadLinkState === "fetching") {
			return "Do you really want to leave? Download will be cancelled.";
		} else {
			return;
		}
	};

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment, _));