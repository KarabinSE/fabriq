const blockTypes = import.meta.glob("./*.vue", { eager: true });

export default {
    install(app) {
        Object.entries(blockTypes).forEach((block) => {
            const [path, module] = block;

            const name = path.split("/").pop().replace(".vue", "");

            app.component(name, module.default);
        });
    },
};
