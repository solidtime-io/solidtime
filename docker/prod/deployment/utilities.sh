tinker() {
  if [ -z "$1" ]; then
    php artisan tinker
  else
    php artisan tinker --execute="\"dd($1);\""
  fi
}

# Commonly used aliases
alias ..="cd .."
alias ...="cd ../.."
alias art="php artisan"
