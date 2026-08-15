

import Alpine from 'alpinejs';

window.generatorBuilder = function generatorBuilder(config = {}) {
    const normalizeEntities = (entities) => entities.map((entity, entityIndex) => ({
        name: entity.name || '',
        fields: Array.isArray(entity.fields) ? entity.fields : [],
        relations: Array.isArray(entity.relations)
            ? entity.relations.map((relation, relationIndex) => ({
                _id: relation._id || `existing_${entityIndex}_${relationIndex}`,
                _inverseOf: relation._inverseOf || null,
                _managedInverse: Boolean(relation._managedInverse),
                _pivotCustomized: Boolean(relation._pivotCustomized),
                type: relation.type || 'belongsTo',
                target: relation.target || '',
                pivot_table: relation.pivot_table || relation.pivotTable || '',
            }))
            : [],
    }));

    return {
        projectName: config.projectName || '',
        selectedEntityIndex: Array.isArray(config.entities) && config.entities.length > 0 ? 0 : null,
        entities: Array.isArray(config.entities) ? normalizeEntities(config.entities) : [],
        fieldTypes: ['string', 'text', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime', 'email', 'password'],
        relationTypes: ['belongsTo', 'hasOne', 'hasMany', 'belongsToMany'],
        relationshipSequence: 1,

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
            const entity = this.entities[index];
            this.removeDirectRelationsForEntity(entity);
            this.entities.splice(index, 1);
            this.syncAllRelationships();

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
            const relation = {
                _id: this.createRelationshipId(),
                _inverseOf: null,
                _managedInverse: false,
                _pivotCustomized: false,
                type: 'belongsTo',
                target,
                pivot_table: '',
            };

            entity.relations.push(relation);
            this.syncAllRelationships();
        },

        removeRelation(entity, index) {
            const relation = entity.relations[index];
            if (!relation) {
                return;
            }

            this.removeRelationshipPair(entity, relation);
            this.syncAllRelationships();
        },

        syncAllRelationships() {
            this.ensureRelationshipIds();
            this.removeOrphanManagedInverses();

            this.entities.forEach((entity) => {
                entity.relations = entity.relations
                    .filter((relation) => !relation._managedInverse)
                    .concat(entity.relations.filter((relation) => relation._managedInverse));
            });

            this.entities.forEach((entity, index) => {
                entity.relations
                    .filter((relation) => !relation._managedInverse)
                    .forEach((relation) => {
                        this.prepareRelationForSync(entity, index, relation);
                        this.upsertInverseRelationship(entity, index, relation);
                    });
            });
        },

        prepareRelationForSync(sourceEntity, sourceIndex, relation) {
            if (relation.type !== 'belongsToMany') {
                return;
            }

            const defaultPivot = this.defaultPivotTable(
                this.entityModelName(sourceEntity, sourceIndex),
                this.toPascalCase(relation.target, ''),
            );

            if (!relation.pivot_table || !relation._pivotCustomized) {
                relation.pivot_table = defaultPivot;
                relation._pivotCustomized = false;
            }
        },

        upsertInverseRelationship(sourceEntity, sourceIndex, relation) {
            const sourceName = this.entityModelName(sourceEntity, sourceIndex);
            const targetIndex = this.entities.findIndex((entity, index) => (
                index !== sourceIndex
                && this.entityModelName(entity, index) === this.toPascalCase(relation.target, '')
            ));

            if (!sourceName || targetIndex === -1 || !relation.type) {
                return;
            }

            const targetEntity = this.entities[targetIndex];
            const inverseType = this.inverseRelationshipType(relation.type);

            if (!inverseType) {
                return;
            }

            const existingInverse = targetEntity.relations.find((candidate) => (
                candidate._managedInverse && candidate._inverseOf === relation._id
            ));

            if (existingInverse) {
                existingInverse.type = inverseType;
                existingInverse.target = sourceName;
                existingInverse.pivot_table = relation.pivot_table || '';
                existingInverse._pivotCustomized = Boolean(relation._pivotCustomized);
                return;
            }

            if (this.hasEquivalentRelationship(targetEntity, inverseType, sourceName)) {
                return;
            }

            targetEntity.relations.push({
                _id: this.createRelationshipId(),
                _inverseOf: relation._id,
                _pivotCustomized: Boolean(relation._pivotCustomized),
                type: inverseType,
                target: sourceName,
                pivot_table: relation.pivot_table || '',
                _managedInverse: true,
            });
        },

        updateRelationshipType(entity, entityIndex, relation) {
            if (relation.type === 'belongsToMany') {
                relation.pivot_table = this.defaultPivotTable(
                    this.entityModelName(entity, entityIndex),
                    this.toPascalCase(relation.target, ''),
                );
                relation._pivotCustomized = false;
            }

            this.syncAllRelationships();
        },

        updateRelationshipTarget(entity, entityIndex, relation) {
            if (relation.type === 'belongsToMany' && !relation._pivotCustomized) {
                relation.pivot_table = this.defaultPivotTable(
                    this.entityModelName(entity, entityIndex),
                    this.toPascalCase(relation.target, ''),
                );
            }

            this.syncAllRelationships();
        },

        updatePivotTable(relation) {
            relation.pivot_table = this.cleanTableName(relation.pivot_table);
            relation._pivotCustomized = relation.pivot_table !== '';
            this.syncAllRelationships();
        },

        inverseRelationshipType(type) {
            return {
                belongsTo: 'hasMany',
                hasOne: 'belongsTo',
                hasMany: 'belongsTo',
                belongsToMany: 'belongsToMany',
            }[type] || null;
        },

        hasEquivalentRelationship(entity, type, target) {
            const equivalentTypes = type === 'hasMany' ? ['hasOne', 'hasMany'] : [type];

            return entity.relations.some((relation) => (
                equivalentTypes.includes(relation.type)
                && this.toPascalCase(relation.target, '') === target
            ));
        },

        removeRelationshipPair(sourceEntity, relation) {
            const sourceIndex = this.entities.indexOf(sourceEntity);
            const sourceName = this.entityModelName(sourceEntity, sourceIndex);
            const targetName = this.toPascalCase(relation.target, '');
            const inverseType = this.inverseRelationshipType(relation.type);
            const relationshipId = relation._managedInverse ? relation._inverseOf : relation._id;

            this.entities.forEach((entity, entityIndex) => {
                const entityName = this.entityModelName(entity, entityIndex);

                entity.relations = entity.relations.filter((candidate) => {
                    if (candidate._id === relationshipId || candidate._inverseOf === relationshipId) {
                        return false;
                    }

                    return !(
                        entityName === targetName
                        && !candidate._managedInverse
                        && this.toPascalCase(candidate.target, '') === sourceName
                        && this.relationshipTypesMatch(candidate.type, inverseType)
                    );
                });
            });
        },

        relationshipTypesMatch(type, expectedType) {
            if (expectedType === 'hasMany') {
                return ['hasOne', 'hasMany'].includes(type);
            }

            return type === expectedType;
        },

        removeDirectRelationsForEntity(removedEntity) {
            const removedName = this.toPascalCase(removedEntity?.name, '');
            if (!removedName) {
                return;
            }

            this.entities.forEach((entity) => {
                entity.relations = entity.relations.filter((relation) => (
                    relation._managedInverse || this.toPascalCase(relation.target, '') !== removedName
                ));
            });
        },

        ensureRelationshipIds() {
            this.entities.forEach((entity) => {
                entity.relations.forEach((relation) => {
                    if (!relation._id) {
                        relation._id = this.createRelationshipId();
                    }
                    relation._managedInverse = Boolean(relation._managedInverse);
                    relation._inverseOf = relation._inverseOf || null;
                    relation._pivotCustomized = Boolean(relation._pivotCustomized);
                    relation.pivot_table = relation.pivot_table || '';
                });
            });
        },

        removeOrphanManagedInverses() {
            const directIds = new Set();

            this.entities.forEach((entity) => {
                entity.relations
                    .filter((relation) => !relation._managedInverse)
                    .forEach((relation) => directIds.add(relation._id));
            });

            this.entities.forEach((entity) => {
                entity.relations = entity.relations.filter((relation) => (
                    !relation._managedInverse || directIds.has(relation._inverseOf)
                ));
            });
        },

        createRelationshipId() {
            const id = `relation_${Date.now()}_${this.relationshipSequence}`;
            this.relationshipSequence += 1;

            return id;
        },

        entityModelName(entity, index) {
            return this.toPascalCase(entity?.name, index === undefined ? '' : `Model${index + 1}`);
        },

        relationshipDescription(relation) {
            return relation._managedInverse ? 'Auto-added inverse relationship' : 'Direct relationship';
        },

        defaultPivotTable(source, target) {
            if (!source || !target) {
                return '';
            }

            return [source, target]
                .map((model) => this.toSnakeCase(model))
                .sort()
                .join('_');
        },

        toSnakeCase(value) {
            return String(value || '')
                .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
                .replace(/[^A-Za-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '')
                .toLowerCase();
        },

        cleanTableName(value) {
            return this.toSnakeCase(value);
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

        relationLine(entity, relation) {
            if (!relation.type || !relation.target) {
                return null;
            }

            const target = this.toPascalCase(relation.target, 'TargetModel');
            const pivot = relation.type === 'belongsToMany'
                && relation.pivot_table
                && relation.pivot_table !== this.defaultPivotTable(this.toPascalCase(entity.name, ''), target)
                ? ` pivot ${this.cleanTableName(relation.pivot_table)}`
                : '';

            return `    ${relation.type} ${target}${pivot}`;
        },

        entityBlock(entity, index) {
            const entityName = this.toPascalCase(entity.name, `Model${index + 1}`);
            const fieldLines = entity.fields.length > 0
                ? entity.fields.map((field, fieldIndex) => this.fieldLine(field, fieldIndex)).join('\n')
                : '    # Add field';
            const relationLines = entity.relations.length > 0
                ? `\n${entity.relations.map((relation) => this.relationLine(entity, relation)).filter(Boolean).join('\n')}`
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
