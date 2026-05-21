sudo service apache2 start
sudo service mariadb start

# Genera URL dinamici basati sul nome del Codespace corrente
CODESPACE_BASE="https://${CODESPACE_NAME}-80.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"

echo "======================================================"
echo " Servizi avviati!"
echo "======================================================"
echo " phpMyAdmin : ${CODESPACE_BASE}/phpmyadmin/"
echo " Sito BPIC  : ${CODESPACE_BASE}/SITO/BPIC/dashboard.php"
echo "======================================================"
echo " RICORDA: apri la porta 80 nel tab 'Ports' di VS Code"
echo " (tasto destro sulla riga 80 -> Port Visibility -> Public)"
echo "======================================================"

