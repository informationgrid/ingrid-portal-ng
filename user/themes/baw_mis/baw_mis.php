<?php
namespace Grav\Theme;

use Grav\Common\File\CompiledYamlFile;
use Grav\Common\Theme;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class BawMis extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeInitialized' => ['onThemeInitialized', 0],
            'onThemeDetailMetadataEvent' => ['onThemeDetailMetadataEvent', 0],
            'onThemeSearchHitMetadataEvent' => ['addThemeSearchHitMetadataContent', 0],
        ];
    }

    public function onThemeInitialized()
    {
        if (!$this->isAdmin()) {
            // Load default configuration.
            $file = CompiledYamlFile::instance("themes://{$this->name}/config/override/override" . YAML_EXT);

            if ($file->exists()) {
                $themeOverrideConfig = $file->content();
                $this->config->set(
                    "themes.{$this->name}",
                    array_replace_recursive($this->config(), $themeOverrideConfig)
                );
            }
        }
    }

    public function addThemeSearchHitMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

        $hit->bwastr_name = ElasticsearchHelper::getValueArray($content, "bwstr-bwastr_name");
        $hit->bwastrs = self::getBwaStrs($content);
        $hit->bawauftragsnummer = ElasticsearchHelper::getValue($content, "bawauftragsnummer");
        $hit->bawauftragstitel = ElasticsearchHelper::getValue($content, "bawauftragstitel");
        $hit->data_category = ElasticsearchHelper::getValue($content, "data_category");
        $hit->citation = ElasticsearchHelper::getValue($content, "additional_html_citation_quote");

    }

    private static function getBwaStrs(\stdClass $esHit): array
    {
        $array = [];
        $ids = ElasticsearchHelper::getValueArray($esHit, "bwstr-bwastr-id");
        $froms = ElasticsearchHelper::getValueArray($esHit, "bwstr-strecken_km_von");
        $tos = ElasticsearchHelper::getValueArray($esHit, "bwstr-strecken_km_bis");
        if (!empty($ids) && !empty($froms) && !empty($tos)) {
            for ($i = 0; $i < count($ids); $i++) {
                $id = $ids[$i];
                if (str_ends_with($id, '00')) {
                    $id = substr($id, 0, -2);
                    $id = $id . '01';
                }
                $array[] = [
                    "id" => $id,
                    "from" => $froms[$i],
                    "to" => $tos[$i],
                ];
            }
        }
        return $array;
    }

    public function onThemeDetailMetadataEvent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $esHit = $event['esHit'];
        $lang = $event['lang'];

        $objClass = ElasticsearchHelper::getValue($esHit, 't01_object.obj_class');

        $node = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');

        // Add theme specific variables
        switch ($objClass) {
            case '4':
                $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]';
                $hit->bawauftragsnummer = IdfHelper::getNodeValue($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]';
                $hit->bawauftragstitel = IdfHelper::getNodeValue($node, $xpathExpression);
                break;
            case '6':
                $xpathExpression = './gmd:identificationInfo/*/software/einsatzzweck';
                $hit->einsatzzweck = IdfHelper::getNodeValue($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/software/ErgaenzungsModul/ergaenzungsModul/gco:Boolean';
                $hit->ergaenzungsModul = IdfHelper::getNodeValue($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/software/ErgaenzungsModul/ergaenzteSoftware';
                $hit->ergaenzteSoftware = IdfHelper::getNodeValue($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/software/Programmiersprache/programmiersprache';
                $hit->programmiersprache = IdfHelper::getNodeValueList($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/software/Entwicklungsumgebung/entwicklungsumgebung';
                $hit->entwicklungsumgebung = IdfHelper::getNodeValueList($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/software/Bibliotheken';
                $hit->bibliotheken = IdfHelper::getNodeValueList($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/software/installationsMethode';
                $hit->installationsMethode = IdfHelper::getNodeValue($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/software/Nutzerkreis';
                $hit->nutzerkreis = self::getTableSymbolInfo(
                    $node,
                    $xpathExpression,
                    ["", "./baw/gco:Boolean", "./wsv/gco:Boolean", "./extern/gco:Boolean"],
                    [1, 2, 3],
                    './anmerkungen'
                );
                $xpathExpression = './gmd:identificationInfo/*/software/ProduktiverEinsatz';
                $hit->produktiverEinsatz = self::getTableSymbolInfo(
                    $node,
                    $xpathExpression,
                    ["", "./wsvAuftrag/gco:Boolean", "./fUndE/gco:Boolean", "./andere/gco:Boolean"],
                    [1, 2, 3],
                    './anmerkungen'
                );
                $xpathExpression = './gmd:identificationInfo/*/software/Betriebssystem';
                $hit->betriebssystem = self::getTableSymbolInfo(
                    $node,
                    $xpathExpression,
                    ["", "./windows/gco:Boolean", "./linux/gco:Boolean"],
                    [1, 2],
                    './anmerkungen'
                );
                $xpathExpression = './gmd:identificationInfo/*/software/Installationsort/lokal/gco:Boolean';
                $hit->installationsortlokal = array(
                    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                    'type' => 'bool'
                );
                $xpathExpression = './gmd:identificationInfo/*/software/Installationsort/HLR/hlr/gco:Boolean';
                $hit->installationsorthlr = array(
                    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                    'type' => 'bool'
                );
                $xpathExpression = './gmd:identificationInfo/*/software/Installationsort/HLR/hlrName';
                $hit->installationsorthlrName = array(
                    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                    'type' => 'text'
                );
                $xpathExpression = './gmd:identificationInfo/*/software/Installationsort/Server/server/gco:Boolean';
                $hit->installationsortserver = array(
                    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                    'type' => 'bool'
                );
                $xpathExpression = './gmd:identificationInfo/*/software/Installationsort/Server/servername/text';
                $hit->installationsortservername = array(
                    'values' => IdfHelper::getNodeValueList($node, $xpathExpression),
                    'type' => 'text'
                );
                #$xpathExpression = './gmd:identificationInfo/*/software/Erstellungsvertrag/vertragsNummer';
                #$hit->erstellungsvertragsnummer = array(
                #    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                #    'type' => 'text'
                #);
                #$xpathExpression = './gmd:identificationInfo/*/software/Erstellungsvertrag/datum';
                #$hit->erstellungsvertragsdatum = array(
                #    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                #    'type' => 'date'
                #);
                #$xpathExpression = './gmd:identificationInfo/*/software/Supportvertrag/vertragsNummer';
                #$hit->supportvertragsnummer = array(
                #    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                #    'type' => 'text'
                #);
                #$xpathExpression = './gmd:identificationInfo/*/software/Supportvertrag/datum';
                #$hit->supportvertragsdatum = array(
                #    'value' => IdfHelper::getNodeValue($node, $xpathExpression),
                #    'type' => 'date'
                #);
                #$xpathExpression = './gmd:identificationInfo/*/software/Supportvertrag/anmerkungen';
                #$hit->supportvertragsinfo = array(
                #    'value' => IdfHelper::getNodeValueList($node, $xpathExpression),
                #    'type' => 'text'
                #);
                break;
            default:
                $xpathExpression = './gmd:identificationInfo/*/gmd:aggregationInfo/gmd:MD_AggregateInformation/gmd:aggregateDataSetName/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]';
                $hit->bawauftragsnummer = IdfHelper::getNodeValue($node, $xpathExpression);
                $xpathExpression = './gmd:identificationInfo/*/gmd:aggregationInfo/gmd:MD_AggregateInformation[not(./gmd:associationType/gmd:DS_AssociationTypeCode/@codeListValue = "crossReference")]/gmd:aggregateDataSetName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]';
                $hit->bawauftragstitel = IdfHelper::getNodeValue($node, $xpathExpression);
                break;
        }
        $hit->sourceCodeRights = self::getBoolInfoValue($node, './gmd:identificationInfo/*/software/QuellCodeRechte[./baw/gco:Boolean]', './baw/gco:Boolean', './anmerkungen');
        $hit->useRights = self::getBoolInfoValue($node, './gmd:identificationInfo/*/software/NutzungsRechte[./dritte/gco:Boolean]', './dritte/gco:Boolean', 'anmerkungen');
        $hit->doi = self::getDoi($node);
        $hit->citations = self::getCitations($node);
        $hit->bibliographies = self::getBibliographies($node);

        $xpathExpression = "//gmd:resourceFormat/gmd:MD_Format";
        $xpathExpressionSub = [
            "./gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]",
            "./gmd:version/*[self::gco:CharacterString or self::gmx:Anchor]"
        ];
        $hit->dataFormat = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);;
        $xpathExpression = "//gmd:DQ_DataQuality[./gmd:report/gmd:DQ_QuantitativeAttributeAccuracy]";
        $xpathExpressionSub = [
            "./gmd:lineage//gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]",
            ".//gmd:DQ_QuantitativeAttributeAccuracy//gmd:valueType/gco:RecordType",
            ".//gmd:DQ_QuantitativeAttributeAccuracy//gmd:valueUnit//gml:catalogSymbol",
            ".//gmd:DQ_QuantitativeAttributeAccuracy//gmd:value/gco:Record"
        ];
        $hit->dataQualities = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
        $hit->hierachyLevelName = IdfHelper::getNodeValue($node, "./gmd:hierarchyLevelName/*[self::gco:CharacterString or self::gmx:Anchor]");
        $hit->areaHeight = self::getAreaHeight($node, $lang);

        // Baugrunddynamik-Schlagworte
        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/*/*/gco:CharacterString="Baugrunddynamik-Schlagwortkatalog"]/gmd:keyword/gco:CharacterString';
        $hit->subsoilKeywords = IdfHelper::getNodeValueList($node, $xpathExpression);
        // Bwstr Bezug (Name / Kennung)
        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicDescription';
        $hit->references = IdfHelper::getNodeValue($node, $xpathExpression);

        switch ($objClass) {
            case '1':
                $simulationType = ElasticsearchHelper::getValue($esHit, 'simulation_data_type');
                switch ($simulationType) {
                    case 'Messdaten':

                        $xpathExpressionDefault = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:supplementalInformation/baw:BAW_Metadata/baw:measurement/baw:Measurement';

                    // Messdaten

                        // Messverfahren
                        $xpathExpression = $xpathExpressionDefault . '/baw:measurementMethod/gco:CharacterString';
                        $hit->measurementMethod = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Messgerät (Name/ID/Modell)
                        $xpathExpression = $xpathExpressionDefault . '/baw:measurementDevice/baw:MeasurementDevice';
                        $xpathExpressionSub = [
                            "./baw:deviceName/gco:CharacterString",
                            "./baw:deviceId/gco:CharacterString",
                            "./baw:deviceModel/gco:CharacterString",
                            "./baw:description/gco:CharacterString"
                        ];
                        $hit->measurementDevice = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Zielparameter
                        $xpathExpression = $xpathExpressionDefault . '/baw:measurementParameter/baw:MeasurementParameter';
                        $xpathExpressionSub = [
                            "./baw:parameterName/gco:CharacterString",
                            "./baw:parameterType/gco:CharacterString",
                            "./baw:uom/gco:CharacterString",
                            "./baw:parameterFunction/gco:CharacterString"
                        ];
                        $hit->measurementParameter = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Räumlichkeit
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:measurementSpatiality/gco:CharacterString';
                        $hit->measurementSpatiality = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Messtiefe
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:measurementDepth/baw:MeasurementDepth[./*]';
                        $xpathExpressionSub = [
                            "./baw:measurementDepth/gco:Decimal",
                            "./baw:uom/gco:CharacterString",
                            "./baw:verticalCRS/gmx:Anchor"
                        ];
                        $hit->measurementDepth = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Frequenz der Messung
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:measurementFrequency/gco:Decimal';
                        $hit->measurementFrequency = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Gemittelter Wasserstand
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:meanWaterLevel/baw:MeanWaterLevel';
                        $xpathExpressionSub = [
                            "./baw:waterLevel/gco:Decimal",
                            "./baw:uom/gco:CharacterString"
                        ];
                        $hit->meanWaterLevel = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Pegelnullpunkt
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:gaugeZeroPoint/baw:GaugeZeroPoint';
                        $xpathExpressionSub = [
                            "./baw:gaugeZeroPoint/gco:Decimal",
                            "./baw:uom/gco:CharacterString",
                            "./baw:verticalCRS/gco:CharacterString",
                            "./baw:description/gco:CharacterString"
                        ];
                        $hit->gaugeZeroPoint = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Abfluss (min)
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:minDischarge/gco:Decimal';
                        $hit->minDischarge = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Abfluss (max)
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:maxDischarge/gco:Decimal';
                        $hit->maxDischarge = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Datenqualität (Beschreibung)
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:dataQualityDescription/gco:CharacterString';
                        $hit->dataQualityDescription = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Zeitliche Genauigkeit
                        $xpathExpression = $xpathExpressionDefault . '/baw:hydraulicEngineeringMeasurement/baw:HydraulicEngineeringMeasurement/baw:temporalAccuracy/gco:Decimal';
                        $hit->temporalAccuracy = IdfHelper::getNodeValue($node, $xpathExpression);
                        break;

                    case 'Simulationsdaten':

                        $xpathExpressionDefault = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:supplementalInformation/baw:BAW_Metadata/baw:simulation/baw:Simulation';

                // Simulationsdaten

                        // Simulationsverfahren
                        $xpathExpression = $xpathExpressionDefault . '/baw:simulationMethod/gco:CharacterString';
                        $hit->simulationMethod = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Version
                        $xpathExpression = $xpathExpressionDefault . '/baw:simulationMethodVersion/gco:CharacterString';
                        $hit->simulationMethodVersion = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Erweiterung
                        $xpathExpression = $xpathExpressionDefault . '/baw:simulationMethodDependency/gco:CharacterString';
                        $hit->simulationMethodDependency = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Räumliche Dimensionalität
                        $xpathExpression = $xpathExpressionDefault . '/baw:spatialDimensionality/gco:CharacterString';
                        $hit->spatialDimensionality = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Zeitliche Genauigkeit
                        $xpathExpression = $xpathExpressionDefault . '/baw:timeStepSize/gco:Decimal';
                        $hit->timeStepSize = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Simulationsmodellart
                        $xpathExpression = $xpathExpressionDefault . '/baw:simulationModelType/gco:CharacterString';
                        $hit->simulationModelType = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Simulationsparameter
                        $xpathExpression = $xpathExpressionDefault . '/baw:simulationParameter/baw:SimulationParameter';
                        $xpathExpressionSub = [
                            "./baw:parameterName/gco:CharacterString",
                            "./baw:parameterType/gco:CharacterString",
                            "./baw:parameterValues/gco:RecordType",
                            "./baw:uom/gco:CharacterString"
                        ];
                        $hit->simulationParameter = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);

                    // Bautechnik Simulationsdaten

                        // Objekt
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:object/gco:CharacterString';
                        $hit->object = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Objektteil
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:objectPart/gco:CharacterString';
                        $hit->objectPart = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Untersuchungsziel
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:investigationGoal/gco:CharacterString';
                        $hit->investigationGoal = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Räumliche Dimensionen
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:spatialDimensionality/gco:CharacterString';
                        $hit->spatialDimensionalityStructuralEngineering = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Zeitliche Dimension (Checkbox)
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:timeDimension/gco:Boolean';
                        $hit->timeDimension = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Level der Untersuchung
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:investigationLevel/gco:CharacterString';
                        $hit->investigationLevel = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Untersuchungsstufe
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:investigationStage/gco:CharacterString';
                        $hit->investigationStage = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Berechnungskonzepte (Materiell/Geometrisch linear, Imperfektionen)
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:materialConcept/gco:CharacterString';
                        $hit->materialConcept = IdfHelper::getNodeValue($node, $xpathExpression);
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:geometryConcept/gco:CharacterString';
                        $hit->geometryConcept = IdfHelper::getNodeValue($node, $xpathExpression);
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:imperfections/gco:CharacterString';
                        $hit->imperfections = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Werkstoffe
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:materials/gco:CharacterString';
                        $hit->materials = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Fließgrenze Bewehrung
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:reinforcementYieldStrength/gco:Decimal';
                        $hit->reinforcementYieldStrength = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Fließgrenze Stahl
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:steelYieldStrength/gco:Decimal';
                        $hit->steelYieldStrength = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Betondruckfestigkeit
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:concreteCompressiveStrength/baw:ConcreteCompressiveStrength';
                        $xpathExpressionSub = [
                            "./baw:concreteCompressiveStrength/gco:Decimal",
                            "./baw:parameter/gco:CharacterString"
                        ];
                        $hit->concreteCompressiveStrength = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Materialmodell
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:materialModel/gco:CharacterString';
                        $hit->materialModel = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Elementtypen
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:elementType/gco:CharacterString';
                        $hit->elementType = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Einwirkung
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:impact/gco:CharacterString';
                        $hit->impact = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Physik
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:physics/gco:CharacterString';
                        $hit->physics = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Analysetyp
                        $xpathExpression = $xpathExpressionDefault . '/baw:structuralEngineeringSimulation/baw:StructuralEngineeringSimulation/baw:analysisType/gco:CharacterString';
                        $hit->analysisType = IdfHelper::getNodeValueList($node, $xpathExpression);

                    // CFD Simulationsdaten

                        // BAW-Schiffsname
                        $xpathExpression = $xpathExpressionDefault . '/baw:shipCFD/baw:ShipCFD/baw:shipName/gco:CharacterString';
                        $hit->shipName = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Angaben zur Physik
                        $xpathExpression = $xpathExpressionDefault . '/baw:shipCFD/baw:ShipCFD/baw:statementAboutPhysics/gco:CharacterString';
                        $hit->statementAboutPhysics = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Eigenschaften
                        $xpathExpression = $xpathExpressionDefault . '/baw:shipCFD/baw:ShipCFD/baw:constantCrossSection/gco:Boolean';
                        $hit->constantCrossSection = IdfHelper::getNodeValue($node, $xpathExpression);
                        $xpathExpression = $xpathExpressionDefault . '/baw:shipCFD/baw:ShipCFD/baw:propulsion/gco:Boolean';
                        $hit->propulsion = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Bewegungsarten
                        $xpathExpression = $xpathExpressionDefault . '/baw:shipCFD/baw:ShipCFD/baw:movementTypes/gco:CharacterString';
                        $hit->movementTypes = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Trajektorie
                        $xpathExpression = $xpathExpressionDefault . '/baw:shipCFD/baw:ShipCFD/baw:trajectory/gco:CharacterString';
                        $hit->trajectory = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Zellanzahl
                        $xpathExpression = $xpathExpressionDefault . '/baw:shipCFD/baw:ShipCFD/baw:cellCount/gco:Integer';
                        $hit->cellCount = IdfHelper::getNodeValue($node, $xpathExpression);
                        break;
                    default:
                        break;
                }
                $hit->simulationType = $simulationType;
                break;

            default:
                break;
        }

        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/gmd:CI_Citation/gmd:title/gco:CharacterString="BAW-Schlagwortkatalog"]/gmd:keyword/gco:CharacterString';
        $hit->bawKeywords = IdfHelper::getNodeValueList($node, $xpathExpression);
        if(!empty($hit->searchTerms)) {
            if(!empty($hit->bawKeywords)) {
                foreach ($hit->bawKeywords as $bawKeyword) {
                    if (($key = array_search($bawKeyword, $hit->searchTerms)) !== false) {
                        unset($hit->searchTerms[$key]);
                    }
                }
            }
            if(!empty($hit->subsoilKeywords)) {
                foreach ($hit->subsoilKeywords as $subsoilKeyword) {
                    if (($key = array_search($subsoilKeyword, $hit->searchTerms)) !== false) {
                        unset($hit->searchTerms[$key]);
                    }
                }
            }
            if(!empty($hit->simulationModelType)) {
                foreach ($hit->simulationModelType as $simType) {
                    if (($key = array_search($simType, $hit->searchTerms)) !== false) {
                        unset($hit->searchTerms[$key]);
                    }
                }
            }
            if(!empty($hit->spatialDimensionality)) {
                if (($key = array_search($hit->spatialDimensionality, $hit->searchTerms)) !== false) {
                    unset($hit->searchTerms[$key]);
                }
            }
            if(!empty($hit->simulationMethod)) {
                if (($key = array_search($hit->simulationMethod, $hit->searchTerms)) !== false) {
                    unset($hit->searchTerms[$key]);
                }
            }
            if(!empty($hit->measurementMethod)) {
                foreach ($hit->measurementMethod as $measureMethod) {
                    if (($key = array_search($measureMethod, $hit->searchTerms)) !== false) {
                        unset($hit->searchTerms[$key]);
                    }
                }
            }
        }
    }

    private static function getAreaHeight(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:verticalElement/gmd:EX_VerticalExtent[./*]");
        foreach ($tmpNodes as $tmpNode) {
            $item = [];

            $item[] = array(
                "value" => IdfHelper::getNodeValue($tmpNode, "./gmd:minimumValue/gco:Real"),
                "type" => "text"
            );

            $item[] = array(
                "value" => IdfHelper::getNodeValue($tmpNode, "./gmd:maximumValue/gco:Real"),
                "type" => "text"
            );

            $item[] = array(
                "value" => IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/@xlink:title"),
                "type" => "text"
            );

            $array[] = $item;
        }
        return $array;
    }

    private static function getCitations(\SimpleXMLElement $node): ?array
    {
        $xpathExpression = './gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue="author"]';
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        if (!empty($tmpNodes)) {
            $array = [];
            $array[] = array(
                "author_person" => IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "author_org" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "year" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:dateType/gmd:CI_DateTypeCode/@codeListValue='publication']/gmd:date/gco:Date|./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:dateType/gmd:CI_DateTypeCode/@codeListValue='publication']/gmd:date/gco:DateTime"),
                "title" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "publisher" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='publisher'][1]/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "doi" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(),'doi')]"),
                "doi_type" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier[contains(./gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]/text(),'doi')]/gmd:authority/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]")
            );
            return $array;
        }
        return null;
    }

    private static function getBibliographies(\SimpleXMLElement $node): ?array
    {
        $xpathExpression = './gmd:identificationInfo/*/gmd:aggregationInfo/gmd:MD_AggregateInformation[./gmd:associationType/gmd:DS_AssociationTypeCode/@codeListValue="crossReference"]';
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        if (!empty($tmpNodes)) {
            $array = [];
            foreach ($tmpNodes as $tmpNode) {
                $array[] = array(
                    "author_person" => IdfHelper::getNodeValueList($tmpNode, "./gmd:aggregateDataSetName/gmd:citedResponsibleParty/gmd:CI_ResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "author_org" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:citedResponsibleParty/gmd:CI_ResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "year" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:dateType/gmd:CI_DateTypeCode/@codeListValue='publication']/gmd:date/gco:Date"),
                    "title" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "publisher" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:citedResponsibleParty/gmd:CI_ResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='publisher'][1]/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "doi" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]")
                );
            }
            return $array;
        }
        return null;
    }

    private static function getDoi(\SimpleXMLElement $node): ?array
    {
        $xpathExpression = './idf:doi[./*]';
        $tmpNode = IdfHelper::getNode($node, $xpathExpression);
        if (!empty($tmpNode)) {
            return array(
                array(
                    array(
                        "value" => IdfHelper::getNodeValue($tmpNode, "./id") ?? '',
                        "type" => "text"
                    ),
                    array(
                        "value" => IdfHelper::getNodeValue($tmpNode, "./type") ?? '',
                        "type" => "text"
                    )
                )
            );
        }
        return null;
    }

    private static function getTableSymbolInfo(\SimpleXMLElement $node, string $xpathExpression, array $xpathSubExpressions, array $symbolCols, string $xpathSubEpressionInfo): ?array
    {
        $rows = [];
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $row = [];
            foreach ($xpathSubExpressions as $key => $xpathSubExpression) {
                if ($xpathSubExpression) {
                    $row[] = array(
                        "value" => IdfHelper::getNodeValue($tmpNode, $xpathSubExpression),
                        "type" => in_array($key, $symbolCols) ? "symbol" : "text"
                    );
                } else {
                    $row[] = "";
                }
            }
            $rows[] = $row;
        }
        return array(
            "rows" => $rows,
            "infos" => IdfHelper::getNodeValueList($node, $xpathExpression . '/' . $xpathSubEpressionInfo)
        );
    }

    private static function getBoolInfoValue(\SimpleXMLElement $node, string $xpathExpression, string $xpathSubExpressionBool, string $xpathSubExpressionInfo): ?array
    {
        $tmpNode = IdfHelper::getNode($node, $xpathExpression);
        if ($tmpNode) {
            return array(
                array (
                    "value" => IdfHelper::getNodeValue($tmpNode, $xpathSubExpressionBool),
                    "type" => "bool"
                ),
                array (
                    "values" => IdfHelper::getNodeValueList($tmpNode, $xpathSubExpressionInfo),
                    "type" => "text"
                )
            );
        }
        return null;
    }

}
