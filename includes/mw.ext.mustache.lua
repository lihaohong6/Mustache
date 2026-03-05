-- This is mostly copied from WeirdGloop's Bucket extension.
-- The file is simple enough that it likely doesn't pass the threshold of originality.
-- Still, Bucket is a cool extension and you should check it out.
local mustache = {}
local php

function mustache.setupInterface( options )
    -- Remove setup function
    mustache.setupInterface = nil

    -- Copy the PHP callbacks to a local variable, and remove the global
    php = mw_interface
    mw_interface = nil

    -- Do any other setup here

    -- Install into the mw global
    mw = mw or {}
    mw.ext = mw.ext or {}
    mw.ext.mustache = mustache

    -- Indicate that we're loaded
    package.loaded['mw.ext.mustache'] = mustache
end

function mustache.render(templateName, data)
    local result = php.render(templateName, data or {})
    return result
end

return mustache
