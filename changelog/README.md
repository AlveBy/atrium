# Notes for the next release

Every change a user of Atrium notices gets a note here, one file per change, instead of a line in `CHANGELOG.md`. When every pull request adds its line to the same place in one file, each merge leaves all the others in conflict; files of their own never conflict.

## Writing a note

Put the file into the folder of its section, the same sections as in [Keep a Changelog](https://keepachangelog.com/en/1.1.0/):

| Folder | Section |
|---|---|
| `added/` | Added |
| `changed/` | Changed |
| `deprecated/` | Deprecated |
| `removed/` | Removed |
| `fixed/` | Fixed |
| `security/` | Security |

Name it after the change, in lowercase with hyphens: `changelog/added/kanban-view.md`. It holds the list item exactly as it will read in `CHANGELOG.md`, starting with `- `, one item or several:

```markdown
- A kanban view for module lists: …
```

Write what changes for the user and link the guide, as the entries of `CHANGELOG.md` do. The lint step of `bin/ci` refuses a file outside a section folder, a folder that is not a section, a file that is not `.md` or does not start with `- `, and entries written under `[Unreleased]` in `CHANGELOG.md`.

## Releasing

`php scripts/changelog.php x.y.z` moves the notes into `CHANGELOG.md` as the section of that version, dated today (or the date given after the version), sections in the order above and the notes of a section in the order of their file names. It moves the compare links on and deletes the files; commit the result as the release (CONTRIBUTING.md, "Versions and releases").
