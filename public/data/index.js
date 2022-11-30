class GameMap {
    static LAYER_BASE = 0;
    static LAYER_CASTLE = 1;
    static LAYER_WOOD = 2;
    static LAYER_COLOR = 3;

    static TILEMAP_BASE = 'tiles';
    static TILEMAP_COLOR = 'colors';

    static DEFAULT_X = 0;
    static DEFAULT_Y = 0;

    static ZOOM_DEFAULT = 2.5;
    static ZOOM_MAX = 3.5;
    static ZOOM_SPEED = 500;

    static CENTER_X = 0;
    static CENTER_Y = 0;

    /** @member map Phaser.Tilemaps.Tilemap **/
    static map;

    static water = [];

    static load () {
        GameMap.map = self.make.tilemap({ key: 'map' });

        let tiles = GameMap.map.addTilesetImage('wm', 'tiles');
        let colors = GameMap.map.addTilesetImage('colors', 'colors');

        GameMap.map.createLayer(GameMap.LAYER_BASE, tiles);
        GameMap.map.createLayer(GameMap.LAYER_CASTLE, tiles);
        GameMap.map.createLayer(GameMap.LAYER_WOOD, tiles);
        GameMap.map.createLayer(GameMap.LAYER_COLOR, colors).setVisible(false);

        self.cameras.main.setBounds(GameMap.CENTER_X, GameMap.CENTER_Y, GameMap.map.widthInPixels, GameMap.map.heightInPixels);

        self.cameras.main.setZoom(GameMap.ZOOM_DEFAULT);
        self.cameras.main.centerOn(GameMap.CENTER_X, GameMap.CENTER_Y);
    }

    static waterTimer = 0;
    static initWater () {
        GameMap.water = GameMap.map.filterTiles(function (a) {
            return (a.index >=190 && a.index <= 202) || a.index === 126 || (a.index >=33 && a.index <= 51)
        }, undefined, undefined, undefined, undefined, undefined, undefined, 'base')

        self.time.addEvent({
            delay: 100,                // ms
            callback: function () {
                GameMap.water.forEach(function (w) {

                    let isoZ = (-2 * Math.sin((GameMap.waterTimer + (w.x * 140)) * 0.004)) + (-1 * Math.sin((GameMap.waterTimer + (w.y * 150)) * 0.005));
                    w.alpha = Phaser.Math.Clamp(1 + (isoZ * 0.1), 0.2, 1);
                    GameMap.waterTimer = GameMap.waterTimer + 0.05;
                });
            },
            loop: true
        });
    }

}

class Ui {
    static SCALING = 0.4;

    /** @castleMenu DOMElement **/
    static castleMenu;

    static load() {
        $('.build-modal').show();

        // self.add.dom(700, game.canvas.height/2)
        //     .createFromCache("buildModal")
        //     .setScale(Ui.SCALING, Ui.SCALING)
        //     .setScrollFactor(0)
        //     .setOrigin(0);
    }
}

class Main {
    static config = {
        type: Phaser.WEBGL,
        width: document.documentElement.clientWidth,
        height: document.documentElement.clientHeight,
        backgroundColor: '#2d2d2d',
        parent: 'phaser-example',
        pixelArt: true,
        scene: {
            preload: Main.preload,
            create: Main.create,
            update: Main.update
        },
        dom: {
            createContainer: true
        },
    };

    static preload ()
    {
        this.load.image(GameMap.TILEMAP_COLOR, 'data/tiled/colors.png');
        this.load.image(GameMap.TILEMAP_BASE, 'data/tiled/world_map_tiles.png');
        this.load.tilemapTiledJSON('map', 'data/tiled/clow-main.json');

        this.load.html("castleMenu", "data/templates/castleMenu.html");
    }

    static create ()
    {
        self = this;

        GameMap.load();
        Ui.load();
        // GameMap.initWater();
        Controls.init();
    }

    static update (time, delta)
    {
        Controls.controls.update(delta);
    }
}

class Controls {

    static controls;

    static init () {
        Controls.initKeyboardMovement();
        Controls.initMouseMovement();
        Controls.initKeyboardActions();
        Controls.initZoom();
        Controls.initMapClick();
    }

    static initMouseMovement () {
        let cam = self.cameras.main;

        self.input.on("pointermove", function (p) {
            if (!p.isDown) return;

            cam.scrollX -= (p.x - p.prevPosition.x) / cam.zoom;
            cam.scrollY -= (p.y - p.prevPosition.y) / cam.zoom;
        });
    }

    static initKeyboardMovement () {
        let controlConfig = {
            camera: self.cameras.main,
            left: self.input.keyboard.addKey('a'),
            right: self.input.keyboard.addKey('d'),
            up: self.input.keyboard.addKey('w'),
            down: self.input.keyboard.addKey('s'),
            speed: 0.5
        };

        Controls.controls = new Phaser.Cameras.Controls.FixedKeyControl(controlConfig);
    }

    static initKeyboardActions () {
        self.input.keyboard.on('keyup-SPACE', function () {
            let colorLayer = GameMap.map.getLayer(GameMap.LAYER_COLOR).tilemapLayer;

            colorLayer.setVisible(!colorLayer.visible);
        });
    }

    static initZoom () {
        // Менять масштаб
        self.input.on('wheel', function (pointer, gameObjects, deltaX, deltaY) {
            let cam = self.cameras.main;
            let newZoom = deltaY > 0 ? GameMap.ZOOM_DEFAULT : GameMap.ZOOM_MAX; //cam.zoom

            if (newZoom !== cam.zoom) {
                cam.pan(self.game.input.mousePointer.x, self.game.input.mousePointer.y, GameMap.ZOOM_SPEED, 'Power2');
                cam.zoomTo(newZoom, GameMap.ZOOM_SPEED);
            }

        }, self);
    }

    static initMapClick () {
        self.input.on('pointerup', function (pointer) {
            let tile = GameMap.map.getTileAtWorldXY(pointer.worldX, pointer.worldY, true, null, GameMap.LAYER_CASTLE);
            let index = tile.index;
            
            console.log('::',GameMap.map.getTileAtWorldXY(pointer.worldX, pointer.worldY, true, null, GameMap.LAYER_BASE).index);

            // Найти только тайлы с замком
            if (index >= 160 && index <= 171) {

                if (Ui.castleMenu) {
                    Ui.castleMenu.removeElement();
                }
                Ui.castleMenu = self.add.dom(pointer.worldX, pointer.worldY).createFromCache("castleMenu");
                Ui.castleMenu.setScale(Ui.SCALING, Ui.SCALING);

                Ui.castleMenu.on("click", function(e) {})
            } else {
                if (Ui.castleMenu) {
                    Ui.castleMenu.removeElement();
                }
            }
        }, self);
    }

}

let game = new Phaser.Game(Main.config);