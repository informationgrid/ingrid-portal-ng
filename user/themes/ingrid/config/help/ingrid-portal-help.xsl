<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

    <xsl:output method="xml"/>

    <xsl:template match="/">
        <xsl:for-each select="//chapter">
            <div class="xsmall-24 large-16 xlarge-16 columns">
                <h2><xsl:value-of select="header"/></h2>
                <xsl:for-each select="section">
                <div class="section">
                    <xsl:if test="header/@display != 'false' or not(header/@display)">
                    <a class="anchor">
                        <xsl:attribute name="name"><xsl:value-of select="@help-key" /></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="@help-key" /></xsl:attribute>
                        <span></span>
                    </a>
                    <h3>
                        <span><xsl:value-of select="header"/></span>
                    </h3>
                    </xsl:if>
                    <xsl:apply-templates select="content"/>
                    <xsl:for-each select="section">
                    <a><xsl:attribute name="name"><xsl:value-of select="@help-key" /></xsl:attribute><a/></a>
                    <h4><xsl:value-of select="header"/></h4>
                    <xsl:apply-templates select="content"/>
                    </xsl:for-each>
                </div>
                </xsl:for-each>
            </div>
        </xsl:for-each>
    </xsl:template>

    <xsl:template match="content">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="*">
        <xsl:copy-of select="." />
    </xsl:template>

</xsl:stylesheet>
