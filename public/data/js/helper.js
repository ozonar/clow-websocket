async function easyFetch(action, pageData) {

    let data = new FormData();

    for (let key in pageData) {
        if (typeof pageData[key] == "undefined") {
            continue;
        }

        if (pageData[key] !== null) {
            data.append(key, pageData[key].toString());
        } else {
            console.warn('Key in fetch array exist but empty. Param:' + key);
            return false;
        }
    }

    let responce = await fetch('action/' + action, {
        method: 'POST',
        body: data,
    });

    return responce.json();

}