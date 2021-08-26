export const API_URL = "https://office.stg1-bellagraceglobal.xyz:81/";
export let ACCESS_TOKEN = null;


function setTokenFromMeta() {
    const token = document.querySelector('[name="commission-engine-access-token"]');

    if (token !== null && typeof token.value !== 'undefined') {
        ACCESS_TOKEN = token.value;
    } else {
        throw "COMMISSION ENGINE ACCESS TOKEN IS MISSING"
    }
}

export function createAccessClient (url) {

    setTokenFromMeta();

    if(typeof url === "undefined") {
        url = "";
    }

    const client = axios.create({
        baseURL: `${API_URL}${url}`
    });

    if (!!ACCESS_TOKEN) {
        client.defaults.headers.common['Authorization'] = `Bearer ${ACCESS_TOKEN}`;
    } else {
        alert("COMMISSION ENGINE ERROR: NO ACCESS TOKEN.");
        throw "COMMISSION ENGINE ACCESS TOKEN IS MISSING";
    }

    client.interceptors.response.use((response) => {
        return response;
    }, function (error) {
        const originalRequest = error.config;

        if (error.response.status === 401 && originalRequest.url === `${API_URL}api/auth/refresh`) {
            console.log("RETRY ABORT");
            return Promise.reject(error);
        }

        if (error.response.status === 401 && !originalRequest._retry) {
            console.log("TRYING TO REFRESH TOKEN");
            originalRequest._retry = true;
            return axios.post(`${API_URL}api/auth/refresh`,
                {
                    "token": ACCESS_TOKEN
                })
                .then(res => {
                    if (res.status === 201) {
                        console.log("TOKEN REFRESHED");
                        ACCESS_TOKEN = res.data.token;

                        client.defaults.headers.common['Authorization'] = 'Bearer ' + ACCESS_TOKEN;

                        return client(originalRequest);
                    }
                })
        }

        console.log("ERROR: " + error.response.status);

        return Promise.reject(error);
    })

    return client;
}

export function setupAccessTokenJQueryAjax() {
    jQuery.ajaxSetup({
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + ACCESS_TOKEN);
        }
    });
}

export function parseAxiosErrorData(data) {

    let error = {
        message: "Server error",
        type: "danger",
        data: null,
    }

    if(typeof data.type !== "undefined" && data.type === "ValidationException") {
        let errors = data.info.errors;
        error.message = errors[Object.keys(errors)[0]][0];
    } else if(typeof data.type !== "undefined" && data.type === "AlertException") {
        error.message = data.message;
        error.type = data.info.alert_type;
        error.data = data.info.data;
    } else if(typeof data.message !== "undefined" && typeof data.message === "string") {
        error.message = data.message;
    } else if(typeof data.error !== "undefined" && typeof data.error.message === "string") {
        error.message = data.error.message;
    } else if(typeof data.error !== "undefined" && typeof data.error === "string") {
        error.message = data.error;
    }

    return error;
}
