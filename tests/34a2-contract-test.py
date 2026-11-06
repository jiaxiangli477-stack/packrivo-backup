import re
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
MIGRATE = ROOT / 'deploy/34a-keyword-map.php'
ROLLBACK = ROOT / 'deploy/34a-rollback.php'


class MigrationContractTest(unittest.TestCase):
    def test_indexable_writes_are_limited_to_title_and_description(self):
        source = MIGRATE.read_text(encoding='utf8')
        for column in ('open_graph_title','open_graph_description','twitter_title','twitter_description'):
            self.assertNotRegex(source, r"\$data\[['\"]" + re.escape(column) + r"['\"]\]\s*=")

    def test_rollback_requires_explicit_positional_backup(self):
        result = subprocess.run(['docker','exec','packrivo-34a-wp','php','/tmp/wp','--allow-root','--require=/tmp/34a2/34a-cli.php','eval-file','/tmp/34a2/rollback.php'], capture_output=True, text=True, encoding='utf8')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('Explicit backup path required', result.stdout + result.stderr)


if __name__ == '__main__':
    unittest.main()
