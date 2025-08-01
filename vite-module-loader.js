import fs from 'fs/promises';
import path from 'path';

async function collectModuleAssetsPaths(modulesPath) {
    return await getExportedModulesArrayAttributes(modulesPath, 'paths');
}

async function collectModulePlugins(modulesPath) {
    return await getExportedModulesArrayAttributes(modulesPath, 'plugins');
}

async function getExportedModulesArrayAttributes(modulesPath, attribute) {
    const result = [];
    modulesPath = path.join(__dirname, modulesPath);

    const moduleStatusesPath = path.join(__dirname, 'modules_statuses.json');

    try {
        // Read module_statuses.json
        const moduleStatusesContent = await fs.readFile(moduleStatusesPath, 'utf-8');
        const moduleStatuses = JSON.parse(moduleStatusesContent);

        // Read module directories
        const moduleDirectories = await fs.readdir(modulesPath);

        for (const moduleDir of moduleDirectories) {
            if (moduleDir === '.DS_Store') {
                // Skip .DS_Store directory
                continue;
            }

            // Check if the module is enabled (status is true)
            if (moduleStatuses[moduleDir] === true) {
                const viteConfigPath = path.join(modulesPath, moduleDir, 'vite.config.js');
                const stat = await fs.stat(viteConfigPath);

                if (stat.isFile()) {
                    // Import the module-specific Vite configuration
                    const moduleConfig = await import(viteConfigPath);

                    if (moduleConfig[attribute] && Array.isArray(moduleConfig[attribute])) {
                        result.push(...moduleConfig[attribute]);
                    }
                }
            }
        }
    } catch (error) {
        console.error(`Error reading module statuses or module configurations: ${error}`);
    }

    return result;
}

export { collectModuleAssetsPaths, collectModulePlugins };
