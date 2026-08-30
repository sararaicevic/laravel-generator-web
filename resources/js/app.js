

import Alpine from 'alpinejs';

window.generatorBuilder = function generatorBuilder(config = {}) {
    const uniqueFieldTypes = new Set(['bigInteger', 'date', 'datetime', 'decimal', 'email', 'enum', 'float', 'integer', 'phone', 'string', 'time', 'timestamp', 'url']);
    const featureKeys = ['index', 'create', 'edit', 'show', 'delete'];
    const defaultFeatures = () => ({
        index: true,
        create: true,
        edit: true,
        show: true,
        delete: true,
    });
    const normalizeFeatures = (features) => ({
        ...defaultFeatures(),
        ...(features || {}),
    });
    const normalizeOptions = (options) => {
        if (Array.isArray(options)) {
            return options.map((option) => String(option).trim()).filter(Boolean);
        }

        if (typeof options === 'string') {
            return options.split('|').map((option) => option.trim()).filter(Boolean);
        }

        return [];
    };
    const normalizeAcceptTypes = (accept) => {
        if (Array.isArray(accept)) {
            return accept.map((type) => String(type).trim()).filter(Boolean);
        }

        if (typeof accept === 'string') {
            return accept.split(',').map((type) => type.trim()).filter(Boolean);
        }

        return [];
    };
    const normalizeModelName = (value, fallback = '') => {
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
    };
    const createEntityId = () => `entity_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    const normalizeField = (field) => {
        const metadata = { ...(field.metadata || {}) };
        if (metadata.options !== undefined) {
            const options = normalizeOptions(metadata.options);

            if (options.length > 0) {
                metadata.options = options;
            } else {
                delete metadata.options;
            }
        }

        if (metadata.accept !== undefined) {
            const accept = normalizeAcceptTypes(metadata.accept);

            if (accept.length > 0) {
                metadata.accept = accept;
            } else {
                delete metadata.accept;
            }
        }

        const normalized = {
            _id: field._id || `field_${Date.now()}_${Math.random().toString(16).slice(2)}`,
            name: field.name || '',
            type: field.type || 'string',
            required: Boolean(field.required),
            nullable: field.nullable !== undefined ? Boolean(field.nullable) : !field.required,
            unique: Boolean(field.unique),
            metadata,
        };

        if (normalized.required) {
            normalized.nullable = false;
        }

        if (normalized.nullable || !uniqueFieldTypes.has(normalized.type)) {
            normalized.unique = false;
        }

        return normalized;
    };

    const normalizeEntities = (entities) => entities.map((entity, entityIndex) => ({
        _id: entity._id || `entity_existing_${entityIndex}`,
        name: entity.name || '',
        display_field: entity.display_field || entity.displayField || '',
        features: normalizeFeatures(entity.features),
        fields: Array.isArray(entity.fields) ? entity.fields.map(normalizeField) : [],
        relations: Array.isArray(entity.relations)
            ? entity.relations.map((relation, relationIndex) => ({
                _id: relation._id || `existing_${entityIndex}_${relationIndex}`,
                _inverseOf: relation._inverseOf || null,
                _managedInverse: Boolean(relation._managedInverse),
                _pivotCustomized: Boolean(relation._pivotCustomized),
                type: relation.type || 'belongsTo',
                target: normalizeModelName(relation.target, ''),
                pivot_table: relation.pivot_table || relation.pivotTable || '',
            }))
            : [],
    }));

    return {
        projectName: config.projectName || '',
        selectedEntityIndex: Array.isArray(config.entities) && config.entities.length > 0 ? 0 : null,
        entities: Array.isArray(config.entities) ? normalizeEntities(config.entities) : [],
        fieldTypes: ['string', 'email', 'url', 'phone', 'text', 'integer', 'bigInteger', 'decimal', 'float', 'boolean', 'date', 'datetime', 'timestamp', 'time', 'enum', 'json', 'password', 'file', 'image'],
        featureOptions: [
            { key: 'index', label: 'Index' },
            { key: 'create', label: 'Create' },
            { key: 'edit', label: 'Edit' },
            { key: 'show', label: 'Preview' },
            { key: 'delete', label: 'Delete' },
        ],
        relationTypes: ['belongsTo', 'hasOne', 'hasMany', 'belongsToMany'],
        relationshipSequence: 1,

        get selectedEntity() {
            return this.entities[this.selectedEntityIndex] || null;
        },

        get availableRelationTargets() {
            return this.entities
                .filter((entity, index) => index !== this.selectedEntityIndex && entity.name.trim() !== '')
                .map((entity) => ({
                    name: this.toPascalCase(entity.name, ''),
                }));
        },

        relationTargetOptions(relation) {
            const options = [...this.availableRelationTargets];
            const currentTarget = this.toPascalCase(relation?.target, '');

            if (currentTarget && !options.some((option) => option.name === currentTarget)) {
                options.unshift({ name: currentTarget });
            }

            return options;
        },

        get canSubmit() {
            return this.projectName.trim() !== ''
                && this.entities.length > 0
                && this.entities.every((entity) => entity.name.trim() !== ''
                    && entity.fields.length > 0
                    && entity.fields.every((field) => field.name.trim() !== '' && field.type && this.fieldIsComplete(field))
                    && this.directRelations(entity).every((relation) => relation.type && relation.target));
        },

        addEntity() {
            this.entities.push({
                _id: createEntityId(),
                name: '',
                display_field: '',
                features: defaultFeatures(),
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
                _id: `field_${Date.now()}_${Math.random().toString(16).slice(2)}`,
                type: 'string',
                required: false,
                nullable: true,
                unique: false,
                metadata: {},
            });
        },

        removeField(entity, index) {
            const [field] = entity.fields.splice(index, 1);
            if (field && entity.display_field === this.cleanFieldName(field.name, '')) {
                entity.display_field = '';
            }
        },

        canFieldBeUnique(field) {
            return Boolean(field?.required) && uniqueFieldTypes.has(field?.type);
        },

        uniqueUnavailableReason(field) {
            if (!field?.required) {
                return 'Unique is available only for required fields.';
            }

            if (!uniqueFieldTypes.has(field?.type)) {
                return 'Unique is not available for this field type.';
            }

            return '';
        },

        normalizeFieldRules(field) {
            field.metadata = field.metadata || {};

            if (field.required) {
                field.nullable = false;
            } else if (field.nullable) {
                field.required = false;
            }

            if (!this.canFieldBeUnique(field)) {
                field.unique = false;
            }

            this.normalizeEnumDefault(field);
        },

        fieldSupportsLengthRules(field) {
            return ['string', 'email', 'url', 'phone', 'text', 'password'].includes(field?.type);
        },

        fieldSupportsRangeRules(field) {
            return ['integer', 'bigInteger', 'decimal', 'float', 'date', 'datetime', 'timestamp', 'time'].includes(field?.type);
        },

        fieldSupportsStep(field) {
            return ['integer', 'bigInteger', 'decimal', 'float', 'time'].includes(field?.type);
        },

        fieldSupportsFileRules(field) {
            return ['file', 'image'].includes(field?.type);
        },

        fieldSupportsOptions(field) {
            return field?.type === 'enum';
        },

        fieldIsComplete(field) {
            if (!this.fieldSupportsOptions(field)) {
                return true;
            }

            return this.enumOptionValues(field).length > 0;
        },

        metadataValue(field, key) {
            field.metadata = field.metadata || {};

            return field.metadata[key] ?? '';
        },

        setMetadataValue(field, key, value) {
            field.metadata = field.metadata || {};

            if (value === '' || value === null || value === undefined) {
                delete field.metadata[key];
                return;
            }

            field.metadata[key] = value;
        },

        fieldOptions(field) {
            field.metadata = field.metadata || {};

            if (!Array.isArray(field.metadata.options)) {
                field.metadata.options = typeof field.metadata.options === 'string'
                    ? field.metadata.options.split('|').map((option) => option.trim())
                    : [''];
            }

            if (field.metadata.options.length === 0) {
                field.metadata.options.push('');
            }

            return field.metadata.options;
        },

        fieldAcceptTypes(field) {
            field.metadata = field.metadata || {};

            if (!Array.isArray(field.metadata.accept)) {
                field.metadata.accept = normalizeAcceptTypes(field.metadata.accept);
            }

            if (field.metadata.accept.length === 0) {
                field.metadata.accept.push('');
            }

            return field.metadata.accept;
        },

        enumOptionValues(field) {
            return normalizeOptions(field?.metadata?.options);
        },

        acceptTypeValues(field) {
            return normalizeAcceptTypes(field?.metadata?.accept);
        },

        addFieldOption(field) {
            field.metadata = field.metadata || {};
            const options = Array.isArray(field.metadata.options) ? [...field.metadata.options] : [];
            options.push('');
            field.metadata.options = options;
        },

        updateFieldOption(field, index, value) {
            field.metadata = field.metadata || {};
            const options = Array.isArray(field.metadata.options) ? [...field.metadata.options] : [];
            options[index] = value;
            field.metadata.options = options;
            this.normalizeEnumDefault(field);
        },

        removeFieldOption(field, index) {
            field.metadata = field.metadata || {};
            const options = Array.isArray(field.metadata.options) ? [...field.metadata.options] : [];
            options.splice(index, 1);
            field.metadata.options = options.length > 0 ? options : [''];
            this.normalizeEnumDefault(field);
        },

        addAcceptType(field) {
            field.metadata = field.metadata || {};
            const accept = Array.isArray(field.metadata.accept) ? [...field.metadata.accept] : [];
            accept.push('');
            field.metadata.accept = accept;
        },

        updateAcceptType(field, index, value) {
            field.metadata = field.metadata || {};
            const accept = Array.isArray(field.metadata.accept) ? [...field.metadata.accept] : [];
            accept[index] = value;
            field.metadata.accept = accept;
        },

        removeAcceptType(field, index) {
            field.metadata = field.metadata || {};
            const accept = Array.isArray(field.metadata.accept) ? [...field.metadata.accept] : [];
            accept.splice(index, 1);
            field.metadata.accept = accept.length > 0 ? accept : [''];
        },

        normalizeEnumDefault(field) {
            if (!this.fieldSupportsOptions(field)) {
                return;
            }

            field.metadata = field.metadata || {};
            const defaultValue = field.metadata.default;

            if (!defaultValue) {
                return;
            }

            if (!this.enumOptionValues(field).includes(defaultValue)) {
                delete field.metadata.default;
            }
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

        removeRelation(entity, index, displayedRelation = null) {
            const relation = displayedRelation || this.directRelations(entity)[index];
            if (!relation) {
                return;
            }

            if (relation._managedInverse) {
                this.removeRelationshipPair(entity, relation);
            } else {
                entity.relations = entity.relations.filter((candidate) => candidate._id !== relation._id);
            }

            this.syncAllRelationships();
        },

        syncAllRelationships() {
            this.ensureRelationshipIds();

            const directRows = [];

            this.entities.forEach((entity, index) => {
                this.directRelations(entity).forEach((relation) => {
                    this.prepareRelationForSync(entity, index, relation);
                    directRows.push({ entity, index, relation });
                });
            });

            this.removeStaleManagedInverses();

            directRows.forEach(({ entity, index, relation }) => {
                this.upsertInverseRelationship(entity, index, relation);
            });

            this.removeStaleManagedInverses();
        },

        directRelations(entity) {
            return Array.isArray(entity?.relations)
                ? entity.relations.filter((relation) => !relation._managedInverse)
                : [];
        },

        relationshipRows(entity, entityIndex) {
            if (!entity) {
                return [];
            }

            const directRows = this.directRelations(entity);
            const inverseRows = Array.isArray(entity.relations)
                ? entity.relations.filter((relation) => relation._managedInverse)
                : [];

            return directRows.concat(inverseRows);
        },

        prepareRelationForSync(sourceEntity, sourceIndex, relation) {
            if (relation.type !== 'belongsToMany') {
                relation.pivot_table = '';
                relation._pivotCustomized = false;
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

        updateRelationshipType(entity, entityIndex, relation, value = null) {
            if (value !== null) {
                relation.type = value;
            }

            if (relation.type === 'belongsToMany') {
                relation.pivot_table = this.defaultPivotTable(
                    this.entityModelName(entity, entityIndex),
                    this.toPascalCase(relation.target, ''),
                );
                relation._pivotCustomized = false;
            } else {
                relation.pivot_table = '';
                relation._pivotCustomized = false;
            }

            this.syncAllRelationships();
        },

        updateRelationshipTarget(entity, entityIndex, relation, value = null) {
            if (value !== null) {
                relation.target = this.toPascalCase(value, '');
            }

            if (relation.type === 'belongsToMany' && !relation._pivotCustomized) {
                relation.pivot_table = this.defaultPivotTable(
                    this.entityModelName(entity, entityIndex),
                    this.toPascalCase(relation.target, ''),
                );
            }

            this.syncAllRelationships();
        },

        updatePivotTable(relation, value = null) {
            if (value !== null) {
                relation.pivot_table = value;
            }

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

        hasEquivalentDirectRelationship(entity, type, target) {
            const equivalentTypes = type === 'hasMany' ? ['hasOne', 'hasMany'] : [type];

            return this.directRelations(entity).some((relation) => (
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
            this.removeStaleManagedInverses();
        },

        removeStaleManagedInverses() {
            const directIds = new Set();
            const directRelations = new Map();

            this.entities.forEach((entity, entityIndex) => {
                const sourceName = this.entityModelName(entity, entityIndex);
                entity.relations
                    .filter((relation) => !relation._managedInverse)
                    .forEach((relation) => {
                        directIds.add(relation._id);
                        directRelations.set(relation._id, {
                            sourceName,
                            targetName: this.toPascalCase(relation.target, ''),
                            inverseType: this.inverseRelationshipType(relation.type),
                            pivotTable: relation.pivot_table || '',
                        });
                    });
            });

            this.entities.forEach((entity) => {
                const entityName = this.entityModelName(entity, this.entities.indexOf(entity));
                const seenManaged = new Set();

                entity.relations = entity.relations.filter((relation) => (
                    !relation._managedInverse || (() => {
                        if (!directIds.has(relation._inverseOf)) {
                            return false;
                        }

                        const direct = directRelations.get(relation._inverseOf);
                        const key = `${relation._inverseOf}:${entityName}`;

                        if (!direct || seenManaged.has(key)) {
                            return false;
                        }

                        seenManaged.add(key);

                        return entityName === direct.targetName
                            && relation.type === direct.inverseType
                            && this.toPascalCase(relation.target, '') === direct.sourceName
                            && (relation.pivot_table || '') === direct.pivotTable;
                    })()
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
            this.normalizeFieldRules(field);

            const modifiers = [];
            if (field.required) {
                modifiers.push('required');
            } else if (field.nullable) {
                modifiers.push('nullable');
            }
            if (field.unique) {
                modifiers.push('unique');
            }

            Object.entries(this.dslFieldMetadata(field)).forEach(([key, value]) => {
                if (value === null || value === undefined || value === '') {
                    return;
                }

                modifiers.push(`${key}=${this.dslMetadataValue(value)}`);
            });

            return `    ${this.cleanFieldName(field.name, `field${index + 1}`)}: ${field.type}${modifiers.length ? ` ${modifiers.join(' ')}` : ''}`;
        },

        dslFieldMetadata(field) {
            const metadata = { ...(field.metadata || {}) };

            if (this.fieldSupportsOptions(field)) {
                const options = this.enumOptionValues(field);

                if (options.length > 0) {
                    metadata.options = options;
                } else {
                    delete metadata.options;
                }
            }

            if (metadata.accept !== undefined && ['file', 'image'].includes(field?.type)) {
                const accept = normalizeAcceptTypes(metadata.accept);

                if (accept.length > 0) {
                    metadata.accept = accept.join(',');
                } else {
                    delete metadata.accept;
                }
            } else {
                delete metadata.accept;
            }

            return metadata;
        },

        dslMetadataValue(value) {
            const normalized = Array.isArray(value) ? value.join('|') : String(value);

            if (!/\s/.test(normalized)) {
                return normalized;
            }

            return `"${normalized.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`;
        },

        featureLine(entity) {
            entity.features = normalizeFeatures(entity.features);
            const enabled = featureKeys.filter((feature) => entity.features[feature]);

            if (enabled.length === featureKeys.length) {
                return null;
            }

            return `    features: ${enabled.length ? enabled.join(' ') : 'none'}`;
        },

        displayLine(entity) {
            const fieldName = this.cleanFieldName(entity.display_field || '', '');

            return fieldName ? `    display: ${fieldName}` : null;
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
            const featureLine = this.featureLine(entity);
            const displayLine = this.displayLine(entity);
            const relationLines = entity.relations.length > 0
                ? `\n${entity.relations.filter((relation) => !relation._managedInverse).map((relation) => this.relationLine(entity, relation)).filter(Boolean).join('\n')}`
                : '';

            return `  entity ${entityName} {\n${featureLine ? `${featureLine}\n` : ''}${displayLine ? `${displayLine}\n` : ''}${fieldLines}${relationLines}\n  }`;
        },

        get dslSource() {
            const appName = this.toPascalCase(this.projectName, 'GeneratedApplication');
            const entityBlocks = this.entities.length > 0
                ? this.entities.map((entity, index) => this.entityBlock(entity, index)).join('\n\n')
                : '  # Add model';

            return `app ${appName} {\n${entityBlocks}\n}`;
        },

        get builderStateJson() {
            return JSON.stringify({
                projectName: this.projectName,
                entities: this.entities,
            });
        },
    };
};

window.Alpine = Alpine;

Alpine.start();
