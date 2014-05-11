%% Grammar
%% <blck> ::= begin <stmts> end
%% 
%% <stmts> ::= <empty>
          %% | <stmt> <stmts>
%% 
%% <stmt> ::= pass
%%          | declare <name>
%%          | use <name>
%%          | <blck>

%% Statement
stmt([pass|More], More).
stmt([declare,X|More], More) :- not(nonTerminal(X)).
stmt([use,X|More], More) :-  not(nonTerminal(X)).
stmt(A) :- stmt(A, []).

%% Statements
stmts([pass|More], More).
stmts([Keyword, Var|More], More) :- stmt([Keyword,Var]).
stmts([pass|More], Other) :- stmts(More,Other).
stmts([Keyword, Var|More], Other) :- stmt([Keyword,Var]), stmts(More,Other).
stmts(A) :- stmts(A, []).

%% Block

%% Facts
nonTerminal(pass).
nonTerminal(declare).
nonTerminal(use).
nonTerminal(begin).
nonTerminal(end).
