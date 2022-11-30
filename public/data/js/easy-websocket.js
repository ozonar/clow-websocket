


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