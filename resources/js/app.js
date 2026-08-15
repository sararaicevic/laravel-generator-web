

import Alpine from 'alpinejs';

window.generatorBuilder = function generatorBuilder(config = {}) {
    return {
        projectName: config.projectName || '',
        selectedEntityIndex: Array.isArray(config.entities) && config.entities.length > 0 ? 0 : null,
        entities: Array.isArray(config.entities) ? config.entities : [],
        fieldTypes: ['string', 'text', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime', 'email', 'password'],

        get selectedEntity() {
            return this.entities[this.selectedEntityIndex] || null;
        },

        get canSubmit() {
            return this.projectName.trim() !== ''
                && this.entities.length > 0
                && this.entities.every((entity) => entity.name.trim() !== ''
                    && entity.fields.length > 0
                    && entity.fields.every((field) => field.name.trim() !== '' && field.type));
        },

        addEntity() {
            this.entities.push({
                name: '',
                fields: [],
            });
            this.selectedEntityIndex = this.entities.length - 1;
        },

        removeEntity(index) {
            this.entities.splice(index, 1);

            if (this.entities.length === 0) {
                this.selectedEntityIndex = null;
                return;
            }

            this.selectedEntityIndex = Math.min(index, this.entities.length - 1);
        },

        selectEntity(index) {
            this.selectedEntityIndex = index;
        },

        addField(entity) {
            entity.fields.push({
                name: '',
                type: 'string',
                required: false,
                nullable: true,
                unique: false,
            });
        },

        removeField(entity, index) {
            entity.fields.splice(index, 1);
        },

        entityLabel(entity, index) {
            return entity.name.trim() || `Model ${index + 1}`;
        },

        fieldLabel(field, index) {
            return field.name.trim() || `Polje ${index + 1}`;
        },

        toPascalCase(value, fallback = 'GeneratedApp') {
            const parts = String(value || '')
                .replace(/[^A-Za-z0-9]+/g, ' ')
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            if (parts.length === 0) {
                return fallback;
            }

            return parts
                .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                .join('');
        },

        cleanFieldName(value, fallback = 'field') {
            const parts = String(value || '')
                .replace(/[^A-Za-z0-9]+/g, ' ')
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            if (parts.length === 0) {
                return fallback;
            }

            return parts
                .map((part, index) => {
                    if (index === 0) {
                        return part.charAt(0).toLowerCase() + part.slice(1);
                    }

                    return part.charAt(0).toUpperCase() + part.slice(1);
                })
                .join('');
        },

        fieldLine(field, index) {
            const modifiers = [];
            if (field.required) {
                modifiers.push('required');
            } else if (field.nullable) {
                modifiers.push('nullable');
            }
            if (field.unique) {
                modifiers.push('unique');
            }

            return `    ${this.cleanFieldName(field.name, `field${index + 1}`)}: ${field.type}${modifiers.length ? ` ${modifiers.join(' ')}` : ''}`;
        },

        entityBlock(entity, index) {
            const entityName = this.toPascalCase(entity.name, `Model${index + 1}`);
            const fields = entity.fields.length > 0
                ? entity.fields.map((field, fieldIndex) => this.fieldLine(field, fieldIndex)).join('\n')
                : '    # Dodaj polje';

            return `  entity ${entityName} {\n${fields}\n  }`;
        },

        get dslSource() {
            const appName = this.toPascalCase(this.projectName, 'NazivAplikacije');
            const entityBlocks = this.entities.length > 0
                ? this.entities.map((entity, index) => this.entityBlock(entity, index)).join('\n\n')
                : '  # Dodaj model';

            return `app ${appName} {\n${entityBlocks}\n}`;
        },
    };
};

window.Alpine = Alpine;

Alpine.start();
