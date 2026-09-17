# API reference

Use this section to look up public signatures, return values, defaults, and
failure conditions. The examples under [Inspect fonts](../font-files.md),
[Convert fonts](../conversion/index.md), and [Subset fonts](../subsetting/index.md)
show how to put these contracts to use.

| Looking for | Reference |
| --- | --- |
| Load a face, inspect its data, or select variation coordinates | [Font API](../font-data.md) |
| Create character sets, choose policies, or inspect subset results | [Subset API](subsetting.md) |
| Write a file or return encoded bytes | [Writer contracts](../conversion/writers.md#writer-contracts) |
| Check accepted containers, outline types, and dependencies | [Supported formats](../formats.md) |
| Check which font tables can be preserved or rewritten | [OpenType subsetting support](opentype-subsetting.md) |

Examples use the `Alto\Font` namespace and its public subnamespaces. The
`OpenType` implementation classes and methods marked `@internal` are not
application entry points. Start with `Font::fromFile()` rather than constructing
an internal parsed face.

Font operations use exceptions implementing
`Alto\Font\Exception\FontExceptionInterface`. Catch the specific exception when
the recovery depends on its cause; see [loading failures](../font-files.md#when-a-font-will-not-load) and
[writer failures](../conversion/writers.md#handle-failures)
for practical recovery steps.
