

import Alpine from 'alpinejs';

window.generatorBuilder = function generatorBuilder(config = {}) {
    const normalizeEntities = (entities) => entities.map((entity) => ({
        name: entity.name || '',
        fields: Array.isArray(entity.fields) ? entity.fields : [],
        relations: Array.isArray(entity.relations) ? entity.relations : [],
    }));

    return {
        projectName: config.projectName || '',
        selectedEntityIndex: Array.isArray(config.entities) && config.entities.length > 0 ? 0 : null,
        entities: Array.isArray(config.entities) ? normalizeEntities(config.entities) : [],
        fieldTypes: ['string', 'text', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime', 'email', 'password'],
        relationTypes: ['belongsTo', 'hasOne', 'hasMany', 'belongsToMany'],

        get selectedEntity() {
            return this.entities[this.selectedEntityIndex] || null;
        },

        get availableRelationTargets() {
            return this.entities
                .filter((entity, index) => index !== this.selectedEntityIndex && entity.name.trim() !== '')
                .map((entity, index) => ({
                    name: this.toPascalCase(entity.name, `Model${index + 1}`),
                }));
        },

        get canSubmit() {
            return this.projectName.trim() !== ''
                && this.entities.length > 0
                && this.entities.every((entity) => entity.name.trim() !== ''
                    && entity.fields.length > 0
                    && entity.fields.every((field) => field.name.trim() !== '' && field.type)
                    && entity.relations.every((relation) => relation.type && relation.target));
        },

        addEntity() {
            this.entities.push({
                name: '',
                fields: [],
                relations: [],
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

        addRelation(entity) {
            const target = this.availableRelationTargets[0]?.name || '';
            entity.relations.push({
                type: 'belongsTo',
                target,
            });
        },

        removeRelation(entity, index) {
            entity.relations.splice(index, 1);
        },

        entityLabel(entity, index) {
            return entity.name.trim() || `Model ${index + 1}`;
        },

        fieldLabel(field, index) {
            return field.name.trim() || `Field ${index + 1}`;
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

        relationLine(relation) {
            return `    ${relation.type} ${this.toPascalCase(relation.target, 'TargetModel')}`;
        },

        entityBlock(entity, index) {
            const entityName = this.toPascalCase(entity.name, `Model${index + 1}`);
            const fieldLines = entity.fields.length > 0
                ? entity.fields.map((field, fieldIndex) => this.fieldLine(field, fieldIndex)).join('\n')
                : '    # Add field';
            const relationLines = entity.relations.length > 0
                ? `\n${entity.relations.map((relation) => this.relationLine(relation)).join('\n')}`
                : '';

            return `  entity ${entityName} {\n${fieldLines}${relationLines}\n  }`;
        },

        get dslSource() {
            const appName = this.toPascalCase(this.projectName, 'GeneratedApplication');
            const entityBlocks = this.entities.length > 0
                ? this.entities.map((entity, index) => this.entityBlock(entity, index)).join('\n\n')
                : '  # Add model';

            return `app ${appName} {\n${entityBlocks}\n}`;
        },
    };
};

window.Alpine = Alpine;

Alpine.start();
