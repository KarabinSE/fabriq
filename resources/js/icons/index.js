const icons = import.meta.globEager("./*.vue");

export default {
    install(app) {
        Object.entries(icons).forEach((icon) => {
            const [path, module] = icon;

            const name = path.split("/").pop().replace(".vue", "");

            app.component(name, module.default);
        });
    },
};
