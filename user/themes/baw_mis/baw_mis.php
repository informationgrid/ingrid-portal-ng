<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class BawMis extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeDetailMetadataEvent' => ['onThemeDetailMetadataEvent', 0],
            'onThemeSearchHitMetadataEvent' => ['addThemeSearchHitMetadataContent', 0],
        ];
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

        // Messdaten
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/MeasurementMethod/measurementMethod";
        $hit->measurementMethod = IdfHelper::getNodeValueList($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/spatialOrientation";
        $hit->measurementSpatialOrientation = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/minDischarge";
        $hit->measurementMinDischarge = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/maxDischarge";
        $hit->measurementMaxDischarge = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/measurementFrequency";
        $hit->measurementMeasurementFrequency = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/dataQualityDescription";
        $hit->measurementDataQualityDescription = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/MeasurementDepth[./*]";
        $xpathExpressionSub = ["./depth", "./uom", "./verticalCRS"];
        $hit->measurementMeasurementDepth = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/MeanWaterLevel[./*]";
        $xpathExpressionSub = ["./waterLevel", "./uom"];
        $hit->measurementMeanWaterLevel = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/GaugeDatum[./*]";
        $xpathExpressionSub = ["./datum", "./uom", "./verticalCRS", "./description"];
        $hit->measurementGaugeDatum = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/MeasurementDevice[./*]";
        $xpathExpressionSub = ["./name", "./id", "./model", "./description"];
        $hit->measurementMeasurementDevice = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
        $xpathExpression = "./gmd:identificationInfo/*/measurementInfo/MeasuredQuantities[./*]";
        $xpathExpressionSub = ["./name", "./type", "./uom", "./calculationFormula"];
        $hit->measurementMeasuredQuantities = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResult/gmd:value/gco:Record";
        $hit->timeMeasureValue = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResult/gmd:valueUnit/gml:UnitDefinition/gml:catalogSymbol";
        $hit->timeMeasureUnit = IdfHelper::getNodeValue($node, $xpathExpression);

        $xpathExpression = "//gmd:resourceFormat/gmd:MD_Format";
        $xpathExpressionSub = [
            "./gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]",
            "./gmd:version/*[self::gco:CharacterString or self::gmx:Anchor]"
        ];
        $hit->dataFormat = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);;
        $xpathExpression = './gmd:identificationInfo//gmd:descriptiveKeywords[.//gmd:thesaurusName//gmd:title//*[self::gco:CharacterString or self::gmx:Anchor] = "de.baw.codelist.model.method"]//gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]';
        $hit->procedure = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = './gmd:identificationInfo//gmd:descriptiveKeywords[.//gmd:thesaurusName//gmd:title//*[self::gco:CharacterString or self::gmx:Anchor] = "de.baw.codelist.model.type"]//gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]';
        $hit->modelTypes = IdfHelper::getNodeValueList($node, $xpathExpression);
        $xpathExpression = './gmd:identificationInfo//gmd:descriptiveKeywords[.//gmd:thesaurusName//gmd:title//*[self::gco:CharacterString or self::gmx:Anchor] = "de.baw.codelist.model.dimensionality"]//gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]';
        $hit->spatialDimensionality = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = './gmd:dataQualityInfo//gmd:DQ_AccuracyOfATimeMeasurement//gco:Record';
        $hit->timestepSize = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "//gmd:DQ_DataQuality[./gmd:report/gmd:DQ_QuantitativeAttributeAccuracy]";
        $xpathExpressionSub = [
            "./gmd:lineage//gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]",
            ".//gmd:DQ_QuantitativeAttributeAccuracy//gmd:valueType/gco:RecordType",
            ".//gmd:DQ_QuantitativeAttributeAccuracy//gmd:valueUnit//gml:catalogSymbol",
            ".//gmd:DQ_QuantitativeAttributeAccuracy//gmd:value/gco:Record"
        ];
        $hit->dataQualities = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
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
        $xpathExpression = './gmd:identificationInfo/*/software/Supportvertrag/vertragsNummer';
        $hit->supportvertragsnummer = array(
            'value' => IdfHelper::getNodeValue($node, $xpathExpression),
            'type' => 'text'
        );
        $xpathExpression = './gmd:identificationInfo/*/software/Supportvertrag/datum';
        $hit->supportvertragsdatum = array(
            'value' => IdfHelper::getNodeValue($node, $xpathExpression),
            'type' => 'date'
        );
        $xpathExpression = './gmd:identificationInfo/*/software/Supportvertrag/anmerkungen';
        $hit->supportvertragsinfo = array(
            'value' => IdfHelper::getNodeValueList($node, $xpathExpression),
            'type' => 'text'
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
        $hit->hierachyLevelName = IdfHelper::getNodeValue($node, "./gmd:hierarchyLevelName/*[self::gco:CharacterString or self::gmx:Anchor]");

        $hit->areaHeight = self::getAreaHeight($node, $lang);

        switch ($objClass) {
            case '1':
                $simulationType = ElasticsearchHelper::getValue($esHit, 'simulation_data_type');
                switch ($simulationType) {
                    // BawLaboratoryData
                    case 'Labordaten':
                        // Anlass der Datenerhebung
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/labordaten/AnlassDerDatenerhebung/anlassDerDatenerhebung';
                        $hit->dataCollectionReason = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Probenherkunft
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/labordaten/Probenherkunft/probenherkunft';
                        $hit->sampleOrigin = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Geprüftes Material
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/labordaten/GeprueftesMaterial/geprueftesMaterial';
                        $hit->testedMaterial = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Mess- und Prüfverfahren
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/labordaten/MessUndPruefverfahren';
                        $xpathExpressionSub = [
                            "./messUndPruefverfahren",
                            "./messgeraete",
                            "./Norm/normBezeichnung",
                            "./Norm/ausgabedatum"
                        ];
                        $subTypes = [
                            "text",
                            "text",
                            "text",
                            "date"
                        ];
                        $hit->testMethod = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub, $subTypes);
                        // Zulassungsprüfung (Status)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/labordaten/Zulassungspruefung';
                        $xpathExpressionSub = [
                            "./pruefnummer",
                            "./aufbauDesSystems",
                            "./Sichtbarkeit/sichtbarkeit",
                            "./zulassungspruefung/gco:Boolean"
                        ];
                        $subTypes = [
                            "text",
                            "text",
                            "text",
                            "symbol"
                        ];
                        $hit->isApprovalProcedure = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub, $subTypes);
                        break;
                    // BawMeasurement
                    case 'Messdaten':
                        // Baugrunddynamik-Schlagworte
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/*/*/gco:CharacterString="Baugrunddynamik-Schlagwortkatalog"]/gmd:keyword/gco:CharacterString';
                        $hit->subsoilKeywords = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Raumbezugssystem (Höhe)
                        $xpathExpression = './gmd:referenceSystemInfo/gmd:MD_ReferenceSystem/gmd:referenceSystemIdentifier/gmd:RS_Identifier/gmd:code/gco:CharacterString';
                        $hit->verticalSpatialSystems = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Vertikale Ausdehnung (Min/Max)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:verticalElement/gmd:EX_VerticalExtent';
                        $xpathExpressionSub = [
                            "./gmd:minimumValue",
                            "./gmd:maximumValue"
                        ];
                        $hit->verticalExtent = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Bwstr Bezug (Name / Kennung)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicDescription';
                        $hit->references = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Messverfahren
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/MeasurementMethod/measurementMethod';
                        $hit->measuringMethod = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Messgerät (Name/ID/Modell)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/MeasurementDevice';
                        $xpathExpressionSub = [
                            "./name",
                            "./id",
                            "./model",
                            "./description"
                        ];
                        $hit->gauge = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Zielparameter
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/MeasuredQuantities';
                        $xpathExpressionSub = [
                            "./name",
                            "./type",
                            "./uom",
                            "./calculationFormula"
                        ];
                        $hit->targetParameters = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Räumlichkeit
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/spatialOrientation';
                        $hit->spatiality = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Messtiefe
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/MeasurementDepth';
                        $hit->measuringDepth = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Frequenz der Messung
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/measurementFrequency';
                        $hit->frequency = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Gemittelter Wasserstand
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/MeanWaterLevel';
                        $hit->averageWaterLevel = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Pegelnullpunkt
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/GaugeDatum';
                        $hit->zeroLevel = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Abfluss (min/max)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/minDischarge / maxDischarge';
                        $hit->drain = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Datenqualität (Beschreibung)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/measurementInfo/dataQualityDescription';
                        $hit->dataQualityDescription = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Zeitliche Genauigkeit
                        $xpathExpression = './gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResult/gmd:value/gco:Record';
                        $hit->timestep = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Bautechnik Messdaten
                        // Untersuchungsziel
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikMessdaten/Untersuchungsziel/untersuchungsziel';
                        $hit->researchGoal = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Art der Messung
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikMessdaten/artDerMessung';
                        $hit->measurementType = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Objektidentnr. Wind
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikMessdaten/objektIdentNrWind';
                        $hit->windID = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Messrichtung
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikMessdaten/messrichtung';
                        $hit->measurementDirection = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Messgrößen
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikMessdaten/Messgroessen/messgroessen';
                        $hit->parameter = IdfHelper::getNodeValueList($node, $xpathExpression);
                        break;
                    // BawSimulation
                    case 'Simulationsdaten':
                        // Baugrunddynamik-Schlagworte
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords[./gmd:thesaurusName/*/*/gco:CharacterString="Baugrunddynamik-Schlagwortkatalog"]/gmd:keyword/gco:CharacterString';
                        $hit->subsoilKeywords = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Raumbezugssystem (Höhe)
                        $xpathExpression = '//gmd:referenceSystemInfo/gmd:MD_ReferenceSystem/gmd:referenceSystemIdentifier/gmd:RS_Identifier/gmd:code/gmx:Anchor';
                        $hit->verticalSpatialSystems = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Vertikale Ausdehnung (Min/Max)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:verticalElement/gmd:EX_VerticalExtent';
                        $xpathExpressionSub = [
                            "./gmd:minimumValue/gco:Real",
                            "./gmd:maximumValue/gco:Real",
                            "./gmd:verticalCRS/@xlink:title"
                        ];
                        $hit->verticalExtent = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Bwstr Bezug
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicDescription';
                        $hit->references = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Simulationsverfahren
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/*/*/gco:CharacterString="de.baw.codelist.model.method"]/gmd:keyword/gco:CharacterString';
                        $hit->process = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Räumliche Dimensionalität
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/*/*/gco:CharacterString="de.baw.codelist.model.dimensionality"]/gmd:keyword/gco:CharacterString';
                        $hit->dimensionality = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Simulationsmodellart
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/*/*/gco:CharacterString="de.baw.codelist.model.type"]/gmd:keyword/gco:CharacterString';
                        $hit->simulationModelType = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Simulationsparameter
                        $xpathExpression = '//gmd:DQ_QuantitativeAttributeAccuracy/gmd:result/gmd:DQ_QuantitativeResult';
                        $xpathExpressionSub = [
                            "./gmd:valueType/gco:RecordType",
                            "./anfangsbedingung-TODO",
                            "./gmd:value/gco:Record",
                            "./gmd:valueUnit/gml:UnitDefinition/gml:catalogSymbol"
                        ];
                        $hit->simulationParameter = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Zeitliche Genauigkeit
                        $xpathExpression = '//gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResult/gmd:value/gco:Record';
                        $hit->timestep = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Bautechnik Simulationsdaten
                        // Software (Name/Version)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Software';
                        $xpathExpressionSub = [
                            "./name",
                            "./version"
                        ];
                        $hit->software = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Objekt
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Objekt/objekt';
                        $hit->object = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Objektteil
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Objektteil/objektteil';
                        $hit->objectPart = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Untersuchungsziel
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Untersuchungsziel/untersuchungsziel';
                        $hit->researchGoal = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Räumliche Dimensionen
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Dimensionen/raeumlicheDimensionen';
                        $hit->spatialDimension = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Zeitliche Dimension (Checkbox)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Dimensionen/zeit/gco:Boolean';
                        $hit->timeDimension = IdfHelper::getNodeValue($node, $xpathExpression);
                        // Level der Untersuchung
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/LevelDerUntersuchung/levelDerUntersuchung';
                        $hit->level = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Untersuchungsstufe
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Untersuchungsstufe/untersuchungsstufe';
                        $hit->phase = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Berechnungskonzepte (Materiell/Geometrisch linear, Imperfektionen)
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Berechnungskonzepte';
                        $xpathExpressionSub = [
                            "./materiellLinear/gco:Boolean",
                            "./materiellLinear/gco:Boolean",
                            "./imperfektionen/gco:Boolean"
                        ];
                        $subTypes = [
                            "symbol",
                            "symbol",
                            "symbol"
                        ];
                        $hit->calculationConcept = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub, $subTypes);
                        // Werkstoffe
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Werkstoffe/werkstoffe';
                        $hit->materials = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Fließgrenze Bewehrung
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/GrundlegendeWerkstoffparameter/Bewehrung/fliessgrenzeBewehrung';
                        $hit->reinforcement = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Fließgrenze Stahl
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/GrundlegendeWerkstoffparameter/Stahl/fliessgrenzeStahl';
                        $hit->steel = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Betondruckfestigkeit
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/GrundlegendeWerkstoffparameter/Betondruckfestigkeit';
                        $xpathExpressionSub = [
                            "./betondruckfestigkeit",
                            "./einheit"
                        ];
                        $hit->compressiveStrength = IdfHelper::getNodeValueListWithSubEntries($node, $xpathExpression, $xpathExpressionSub);
                        // Materialmodell
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Materialmodell/materialmodell';
                        $hit->materialModel = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Elementtypen
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Elementtypen/elementtypen';
                        $hit->elementTypes = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Einwirkung
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Einwirkung/einwirkung';
                        $hit->einwirkung = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Physik
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Physik/physik';
                        $hit->physics = IdfHelper::getNodeValueList($node, $xpathExpression);
                        // Analysetyp
                        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/bautechnikSimulationsdaten/Analysetyp/analysetyp';
                        $hit->analysisType = IdfHelper::getNodeValueList($node, $xpathExpression);
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
            if(!empty($hit->process)) {
                if (($key = array_search($hit->process, $hit->searchTerms)) !== false) {
                    unset($hit->searchTerms[$key]);
                }
            }
            if(!empty($hit->dimensionality)) {
                if (($key = array_search($hit->dimensionality, $hit->searchTerms)) !== false) {
                    unset($hit->searchTerms[$key]);
                }
            }
            if(!empty($hit->simulationModelType)) {
                foreach ($hit->simulationModelType as $simulationModelType) {
                    if (($key = array_search($simulationModelType, $hit->searchTerms)) !== false) {
                        unset($hit->searchTerms[$key]);
                    }
                }
            }
            if(!empty($hit->measuringMethod)) {
                foreach ($hit->measuringMethod as $measuringMethod) {
                    if (($key = array_search($measuringMethod, $hit->searchTerms)) !== false) {
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
