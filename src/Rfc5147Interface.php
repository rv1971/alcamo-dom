<?php

namespace alcamo\dom;

/**
 * @brief Provide RFC 5147 data
 *
 * @sa [RFC 5147](https://datatracker.ietf.org/doc/html/rfc5147)
 *
 * @date Last reviewed 2025-10-23
 */
interface Rfc5147Interface
{
    /// Return RFC 5147 `line=` fragment identifier
    public function getRfc5147Fragment(): string;

    /// Return URI with RFC 5147 `line=` fragment identifier
    public function getRfc5147Uri(): string;
}
