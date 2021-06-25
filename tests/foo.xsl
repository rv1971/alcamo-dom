<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet
    version="1.0"
    xmlns="http://www.w3.org/1999/xhtml"
    xmlns:baz="http://baz.example.org"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:variable name="foo" select="//@qux"/>

  <xsl:output
      method="text"
      indent="no"
      media-type="application/json"/>
</xsl:stylesheet>
