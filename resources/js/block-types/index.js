const blockTypes = import.meta.globEager("./*.vue");

export default {
    install(app) {
        Object.entries(blockTypes).forEach((block) => {
            const [path, module] = block;

            const name = path.split("/").pop().replace(".vue", "");

            app.component(name, module.default);
        });
    },
};
