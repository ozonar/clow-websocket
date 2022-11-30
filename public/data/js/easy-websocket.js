class EasyWebSocket {

    constructor(serverIp, onMessageCallback) {
        this.browserId = Identity.getBrowserId();
        this.createWebSocket(serverIp, onMessageCallback);
        this.queue = new SendQueue(this.conn, this.browserId)
    }

    createWebSocket(currLocation, onMessageCallback) {

        this.conn = new ReconnectingWebSocket('ws://' + currLocation);

        this.conn.onopen = function () {
            console.info("Connection established!");

            WsHelper.dispatchEvent('websocket:ready');
        };

        this.conn.onerror = function () {
            console.info("Connection error!");
        };

        this.conn.onclose = function () {
            console.info("Connection close!");
        };

        this.conn.onmessage = function (e) {
            onMessageCallback(e);
            // EasyWebSocket.receiveMessage(e);
        };
    }
}

class SendQueue {
    constructor(connection, browserId) {
        this.connection = connection;
        this.browserId = browserId;
    }

    addToQueue(type, parameters) {

        if (typeof this.queue === "undefined") {
            this.clearQueue();
        }

        parameters['type'] = type;
        parameters['playerId'] = this.browserId;

        this.queue.push(parameters);
        return this;
    }

    getQueue() {
        return this.queue;
    }

    clearQueue() {
        this.queue = [];
    }

    solveQueue() {
        let messageArray = JSON.stringify(this.getQueue());
        this.connection.send(messageArray);

        this.clearQueue();
    }

    sendNow(type, parameters) {
        this.addToQueue(type, parameters);
        this.solveQueue();
    }
}

class Identity {
    static getBrowserId() {

        let id = Number(localStorage.getItem('browser-id'));

        if (!id) {
            id = Math.round(Math.random() * 10000);
            localStorage.setItem('browser-id', id.toString());
        }

        return id;
    }
}

class WsHelper {
    static dispatchEvent(name) {
        const event = new Event(name);
        document.dispatchEvent(event);
    }
}



class A {
    constructor(onMessageCallback) {
        this.onMessageCallback = onMessageCallback;
    }

    init () {
        this.conn = new WebSocket('localhost');
        this.conn.onmessage = function () {
            this.onMessageCallback() // this в данном контексте не класс А
        };
    }
}