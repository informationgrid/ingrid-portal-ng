<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

    <xsl:output method="xml"/>

    <xsl:template match="/">
        <xsl:for-each select="//chapter">
            <xsl:if test="header/@display != 'false' or not(header/@display)">
                <ul class="accordion filter-group nav-group" data-accordion="" data-multi-expand="false" data-allow-all-closed="true" role="tablist">
                    <li class="accordion-item">
                        <a class="accordion-title" role="tab" aria-expanded="false" aria-selected="false">
                            <xsl:attribute name="href">?hkey=<xsl:value-of select="section/@help-key" /></xsl:attribute>
                            <span class="text"><xsl:value-of select="header"/></span>
                        </a>
                        <div class="accordion-content" data-tab-content="" role="tabpanel">
                            <div class="boxes">
                                <xsl:for-each select="section">
                                    <a class="js-anchor-target js-anchor-target-entry" data-key="hkey">
                                        <xsl:attribute name="href">#<xsl:value-of select="@help-key" /></xsl:attribute>
                                        <span class="text"><xsl:value-of select="header"/></span>
                                    </a>
                                </xsl:for-each>
                            </div>
                        </div>
                    </li>
                </ul>
            </xsl:if>
        </xsl:for-each>
    </xsl:template>

    <xsl:template match="chapter">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="*">
        <xsl:copy-of select="." />
    </xsl:template>

</xsl:stylesheet>
