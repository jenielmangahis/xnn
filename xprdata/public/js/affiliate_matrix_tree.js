(function ($, api_url, Vue, swal, axios, location, undefined) {


    const client = commissionEngine.createAccessClient(`member/matrix-tree`);
    commissionEngine.setupAccessTokenJQueryAjax();

    let tree = null;

    const vm = new Vue({
        el: "#matrix-tree",
        mounted() {
            this.root_id = $('#member').val();
            this.owner_id = $('#member').val();
            this.loadMatrixTree(this.root_id);
            this.initializeJQueryEvents();
        },
        data: {
            is_fetching: 0,
            tree: null,
            breadcrumb: [],
            root_id: null,
            owner_id: null,
            parent_id: null,
            hasUnplacedMember: null,
            autocomplete_url: `${api_url}common/autocomplete/matrix-downline`,
            downline_id: null,
            member: {
                user_id: null,
                parent_id: null,
                name: null,
                current_rank: null,
                psp: null,
                gvp: null,
                active_associates: null,
                active_customers: null,
                three_month_sales: null,
            },
        },
        methods: {
            initializeJQueryEvents() {
                let _this = this;

                $('#chart-container').on('click', '.node', function () {
                    let node = $(this).data('nodeData');
                    _this.loadMatrixTree(node.user_id);
                });
            },
            loadMatrixTree(user_id) {

                if (+user_id === +this.parent_id) return;

                this.getDownline(user_id, (downline, breadcrumb) => {
                    this.parent_id = user_id;
                    this.breadcrumb = breadcrumb;

                    let tree_config = {
                        data: downline,
                        // verticalLevel: 3,
                        nodeId: "user_id",
                        nodeContent: 'name',
                        nodeTitle: "user_id",
                        toggleSiblingsResp: false,
                        parentNodeSymbol: null,
                        // zoom: true,
                        nodeTemplate(data) {
                            return `
                                <div class="title">#${data.user_id}</div>
                                <div class="content" style="display: block;">
                                    <p class="content-name">${data.member}</p>
                                    <span class="content-current-rank">Rank: <strong>${data.paid_as_rank}</strong></span>
                                </div>
                            `;
                        },
                        initCompleted() {
                            vm.initializeTooltip();
                        },
                    };

                    if (tree !== null) {
                        tree.init(tree_config);
                    } else {
                        tree = $('#chart-container').orgchart(tree_config);
                    }

                    let outerContent = $('#chart-container');
                    let innerContent = $('#chart-container > .orgchart');
                    outerContent.scrollLeft((innerContent.width() - outerContent.width()) / 2)
                });
            },
            getDownline(user_id, callback) {

                if (this.is_fetching === 1) return;

                this.is_fetching = 1;

                client.get(`${this.root_id}/downline/${user_id}`).then(response => {
                    this.is_fetching = 0;
                    let data = response.data.downline;

                    const root = [];
                    const map = {};

                    data.forEach(node => {

                        // node.className = +node.is_abp || +node.is_qualified_override ? "node-green" : "node-red";

                        if (+node.is_customer === 1) {
                            node.className = "node-yellow";
                        } else if (+node.is_active) {
                            node.className = "node-green";
                        } else {
                            node.className = "node-red";
                        }

                        if (+node.user_id === +user_id) return root.push(node);

                        let parentIndex = map[node.parent_id];
                        if (typeof parentIndex !== "number") {
                            parentIndex = data.findIndex(el => el.user_id === node.parent_id);
                            map[node.parent_id] = parentIndex;
                        }

                        if (parentIndex < 0) {
                            console.log(node);
                            return true;
                        }

                        if (!data[parentIndex].children) {
                            return data[parentIndex].children = [node];
                        }

                        data[parentIndex].children.push(node);
                    });

                    callback(root[0], response.data.breadcrumb);
                }).catch(error => {
                    this.is_fetching = 0;
                    // swal("Unable to downline", "", "error")
                    console.log(error);
                    callback({}, []);
                });
            },
            selectionChange(value) {

                if (!!value) {
                    this.root_id = value;
                } else {
                    this.root_id = this.owner_id;
                }

                this.loadMatrixTree(this.root_id);
            },
            initializeTooltip() {
                tippy('.orgchart .node', {
                    placement: 'right',
                    content: 'Loading...',
                    allowHTML: true,
                    theme: 'light',
                    followCursor: true,

                    // hideOnClick: false,
                    // trigger: 'click',

                    // flipOnUpdate: true,
                    onCreate(instance) {
                        // Setup our own custom state properties
                        instance._isFetching = false;
                        instance._rankDetails = null;
                        instance._error = null;
                        instance._nodeData = null;
                    },
                    onShow(instance) {
                        if (instance._isFetching || instance._rankDetails || instance._error) {
                            return;
                        }

                        instance._nodeData = $(instance.reference).closest('.node').data('nodeData');

                        instance._isFetching = true;

                        client.get(`${instance._nodeData.user_id}/current-rank-details`)
                            .then(response => {

                                let data = instance._rankDetails = response.data;
                                let member = instance._nodeData;
                                let is_customer = +member.is_customer;

                                let content = `
                                    <h5><strong>${member.member}</strong></h5>
                                    
                                    <h5>Enrolled Date:</h5>
                                    <p>${member.enrolled_date}</p>
                                    
                                    <h5>Category:</h5>
                                    <p>${is_customer ? "Customer" : "Affiliate"}</p>
                                    
                                    <h5>Highest Achieved Rank:</h5>
                                    <p>${is_customer ? "Customer" : member.highest_rank}</p>
                                    
                                    <h5>Paid-as Rank:</h5>
                                    <p>${is_customer ? "Customer" : member.paid_as_rank}</p>
                                    
                                    <h5>Current Rank:</h5>
                                    <p>${is_customer ? "Customer" : member.current_rank}</p>
                                `;


                                if(is_customer)
                                {
                                    instance.setContent(content);
                                    return;
                                }

                                content +=`
     
                                    <h5></h5>
                                    <p>Coach Points: <span>${(+member.coach_points).toFixed(2)}</span></p>
                                    <p>Referral Points: <span>${(+member.referral_points).toFixed(2)}</span></p>
                                    <p>Organization Points: <span>${(+member.organization_points).toFixed(2)}</span></p>
                                    <p>Team Group Points: <span>${(+member.team_group_points).toFixed(2)}</span></p>
                                    <p>Preferred Customers: <span>${(+member.preferred_customer_count).toFixed(2)}</span></p>

                                `;

                                content += `
                                    <h5>Needs For Next Rank</h5>
                                    <p>Next Rank: <span>${ data.next_rank }</span></p>
                                `;

                                for(let i = 0; i < data.needs.length; i++) {
                                    let n = data.needs[0];

                                    content += `<p>${n.description}: <span>${n.value}</span></p>`;
                                }

                                instance.setContent(content);
                            })
                            .catch(error => {
                                instance._error = error;
                                instance.setContent(`Request failed. ${error}`);
                            })
                            .finally(() => {
                                instance._isFetching = false;
                            });
                    },
                    onHidden(instance) {

                        if (instance._rankDetails === null) {
                            instance.setContent('Loading...');
                            instance._error = null;
                        }

                    }
                });
            }

        },
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));